<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Error'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'TH Sarabun New', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .error-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 60px 40px;
            text-align: center;
            max-width: 600px;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .error-code {
            font-size: 60px;
            font-weight: bold;
            color: #667eea;
            margin: 20px 0;
        }
        
        .error-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 20px 0;
        }
        
        .error-description {
            font-size: 16px;
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
        }
        
        .error-message {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: left;
            font-family: monospace;
            color: #333;
        }
        
        .btn-home {
            margin-top: 30px;
            padding: 12px 40px;
            font-size: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            border-radius: 50px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .error-details {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 14px;
            color: #999;
        }
        
        .footer-links {
            margin-top: 20px;
        }
        
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <?php if (isset($code)): ?>
            <div class="error-code"><?php echo htmlspecialchars($code); ?></div>
        <?php endif; ?>
        
        <div class="error-title">
            <?php echo htmlspecialchars($title ?? 'Error'); ?>
        </div>
        
        <div class="error-description">
            <?php echo htmlspecialchars($description ?? ''); ?>
        </div>
        
        <?php if (isset($message) && !empty($message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <a href="/" class="btn btn-home">
            <i class="fas fa-home"></i> กลับไปหน้าแรก
        </a>
        
        <div class="error-details">
            <p>หากปัญหายังคงเกิดขึ้น โปรดติดต่อ Admin</p>
        </div>
        
        <div class="footer-links">
            <a href="/certificate/">Dashboard</a>
            <a href="/certificate/contact.php">ติดต่อเรา</a>
            <a href="/certificate/help.php">ช่วยเหลือ</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
