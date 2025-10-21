<?php
require_once "db.php";
require_once "assets/validation.class.php";
session_start();

$success_msg = null;
$errors = [];

if ($_POST) {
    $validator = new Validation();
    $validator->required("email", $_POST["email"] ?? null, "Email");
    $validator->email("email", $_POST["email"] ?? null, "Email");
    
    if ($validator->isValid()) {
        $email = $validator->getValidatedData()["email"];
        
        // Generate reset token (valid for 1 hour)
        $reset_token = bin2hex(random_bytes(32));
        $expiry_time = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
        
        // Update user with reset token
        $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_token_expiry=? WHERE email=?");
        $stmt->bind_param("sss", $reset_token, $expiry_time, $email);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // In production, send email here
                // For now, show the reset link for testing
                $reset_link = "http://localhost:8080/certificate/reset_password.php?token=" . $reset_token;
                $success_msg = "Reset link: <a href='" . htmlspecialchars($reset_link) . "' target='_blank'>Click here to reset password</a>";
            } else {
                $errors['email'][] = "Email not found in system";
            }
        } else {
            $errors['database'][] = "Error processing request";
        }
        $stmt->close();
    } else {
        $errors = $validator->getErrors();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
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
            <h2>Forgot Password</h2>
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $field_errors): ?>
                            <?php foreach ($field_errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <input type="email" name="email" class="form-control mb-3" placeholder="your@email.com" required>
                <button class="btn btn-primary w-100">Send Link</button>
            </form>
            <a href="login.php">Back</a>
        </div>
    </div>
</body>
</html>