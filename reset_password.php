<?php
require_once "db.php";
require_once "assets/validation.class.php";
session_start();

$token = $_GET["token"] ?? null;
$message = null;
$error = null;
$can_reset = false;

// Validate token
if ($token) {
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE reset_token=? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $can_reset = true;
    } else {
        $error = "Invalid or expired reset link";
    }
    $stmt->close();
}

// Handle password reset
if ($_POST && $can_reset) {
    $new_password = $_POST["password"] ?? null;
    $confirm_password = $_POST["confirm_password"] ?? null;
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Both password fields are required";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_token_expiry=NULL WHERE reset_token=?");
        $stmt->bind_param("ss", $hashed_password, $token);
        
        if ($stmt->execute()) {
            $message = "Password reset successfully! <a href=\"login.php\">Login here</a>";
            $can_reset = false;
        } else {
            $error = "Error resetting password";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .box {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        h2 {
            color: #667eea;
        }
        button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h2>Reset Password</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($can_reset): ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <a href="login.php" class="mt-3 d-block">Back to Login</a>
        </div>
    </div>
</body>
</html>