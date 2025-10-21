<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - หน้าไม่พบ</title>
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
        }
        
        .error-icon {
            font-size: 120px;
            color: #667eea;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .error-code {
            font-size: 80px;
            font-weight: bold;
            color: #667eea;
        }
        
        .error-title {
            font-size: 32px;
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
        
        .btn-group-custom {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-custom {
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 50px;
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .btn-secondary-custom {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary-custom:hover {
            background: #667eea;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-search"></i>
        </div>
        
        <div class="error-code">404</div>
        
        <div class="error-title">
            หน้าไม่พบ
        </div>
        
        <div class="error-description">
            ขออภัย หน้าที่คุณกำลังค้นหาไม่พบในระบบ อาจเป็นไปได้ว่า URL ไม่ถูกต้องหรือหน้านี้ถูกลบไปแล้ว
        </div>
        
        <div class="btn-group-custom">
            <a href="/" class="btn-custom btn-primary-custom">
                <i class="fas fa-home"></i> กลับไปหน้าแรก
            </a>
            <a href="/certificate/" class="btn-custom btn-secondary-custom">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </div>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 14px;">
            <p>หากคุณเชื่อว่านี่คือข้อผิดพลาด <a href="javascript:history.back()" style="color: #667eea;">ย้อนกลับไป</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
