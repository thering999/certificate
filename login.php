<?php
require_once 'db.php';
require_once 'assets/validation.class.php';
require_once 'assets/error_handler.php';

session_start();

$error = null;
$errors = [];
$attempt_count = 0;
$max_attempts = 5;
$lockout_time = 300; // 5 minutes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_attempts) {
        $lockout_at = $_SESSION['login_lockout_time'] ?? 0;
        if (time() - $lockout_at < $lockout_time) {
            $remaining = $lockout_time - (time() - $lockout_at);
            $error = "บัญชีถูกล็อก กรุณารอ $remaining วินาที";
        } else {
            $_SESSION['login_attempts'] = 0;
        }
    }
    
    if (!$error) {
        $validator = new Validation();
        
        // Validate input
        $validator->required('username', $_POST['username'] ?? '', 'ชื่อผู้ใช้');
        $validator->required('password', $_POST['password'] ?? '', 'รหัสผ่าน');
        
        if ($validator->isValid()) {
            $data = $validator->getValidatedData();
            $username = $data['username'];
            $password = $data['password'];
            
            // Query database
            $stmt = $conn->prepare('SELECT id, password, role FROM users WHERE username = ?');
            if (!$stmt) {
                ErrorHandler::logDB($conn->error);
                $error = 'เกิดข้อผิดพลาดในระบบ';
            } else {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($id, $hash, $role);
                    $stmt->fetch();
                    $stmt->close();
                    
                    // Verify password
                    if (password_verify($password, $hash)) {
                        // Reset login attempts on success
                        $_SESSION['login_attempts'] = 0;
                        
                        // Set session with correct role from database
                        $_SESSION['user_id'] = $id;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;  // Use the role from database query (line 43)
                        $_SESSION['login_time'] = time();
                        
                        // Log successful login
                        ErrorHandler::log("User logged in: $username", 'INFO');
                        
                        header('Location: index.php');
                        exit;
                    } else {
                        // Invalid password - increment attempt counter
                        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                        $_SESSION['login_lockout_time'] = time();
                        
                        // Log failed attempt
                        ErrorHandler::logSecurity('LOGIN_FAILED', "Username: $username, IP: " . $_SERVER['REMOTE_ADDR']);
                        
                        $error = 'รหัสผ่านไม่ถูกต้อง';
                    }
                } else {
                    $stmt->close();
                    
                    // Username not found - increment attempt counter
                    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                    $_SESSION['login_lockout_time'] = time();
                    
                    // Log failed attempt
                    ErrorHandler::logSecurity('LOGIN_FAILED', "Username not found: $username, IP: " . $_SERVER['REMOTE_ADDR']);
                    
                    $error = 'ไม่พบชื่อผู้ใช้นี้';
                }
            }
        } else {
            $errors = $validator->getErrors();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ - Certificate System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .login-bg {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            background: rgba(255,255,255,0.97);
            padding: 2.5rem 2rem;
            animation: slideUp 0.6s ease-out;
        }
        .login-title {
            font-weight: 700;
            font-size: 2rem;
            color: #764ba2;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 1rem;
        }
        .logo-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            background: white;
            padding: 5px;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-login {
            width: 100%;
            font-size: 1.1rem;
        }
        .login-links {
            text-align: center;
            margin-top: 1.2rem;
        }
        @media (max-width: 600px) {
            .login-card { padding: 1.5rem 0.5rem; }
            .login-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body class="login-bg">
    <div class="login-card">
        <div class="logo-container">
            <img src="assets/logomoph.png" alt="กระทรวงสาธารณสุข Logo" class="logo-img">
        </div>
        <div class="login-title">เข้าสู่ระบบใบประกาศ</div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-warning">
                <strong><i class="fas fa-check-circle"></i> โปรดแก้ไขข้อผิดพลาด:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $field => $messages): ?>
                        <?php foreach ($messages as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-user"></i> ชื่อผู้ใช้</label>
                <input type="text" name="username" class="form-control form-control-lg <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                       required autofocus value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-lock"></i> รหัสผ่าน</label>
                <input type="password" name="password" class="form-control form-control-lg <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-custom btn-login">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </button>
        </form>
        <div class="login-links">
            <a href="register.php" class="btn btn-link"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a> |
            <a href="forgot_password.php" class="btn btn-link"><i class="fas fa-key"></i> ลืมรหัสผ่าน?</a>
        </div>
    </div>
</body>
</html>
