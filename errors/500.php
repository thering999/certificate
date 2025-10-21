<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - เกิดข้อผิดพลาดในระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            color: #f5576c;
            margin-bottom: 20px;
            animation: shake 0.5s infinite;
        }
        
        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
        
        .error-code {
            font-size: 80px;
            font-weight: bold;
            color: #f5576c;
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
        
        .status-box {
            background: #f8f9fa;
            border-left: 4px solid #f5576c;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: left;
        }
        
        .status-item {
            margin: 8px 0;
            font-size: 14px;
        }
        
        .status-item strong {
            color: #f5576c;
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .btn-secondary-custom {
            background: white;
            color: #f5576c;
            border: 2px solid #f5576c;
        }
        
        .btn-secondary-custom:hover {
            background: #f5576c;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-server"></i>
        </div>
        
        <div class="error-code">500</div>
        
        <div class="error-title">
            เกิดข้อผิดพลาดในระบบ
        </div>
        
        <div class="error-description">
            ขออภัย ระบบพบข้อผิดพลาดบางอย่าง ทีมงานของเรากำลังแก้ไขปัญหา โปรดลองใหม่อีกครั้งในอีกสักครู่
        </div>
        
        <div class="status-box">
            <div class="status-item">
                <strong>🕐 เวลา:</strong> <?php echo date('Y-m-d H:i:s'); ?>
            </div>
            <div class="status-item">
                <strong>📍 IP Address:</strong> <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown'); ?>
            </div>
            <div class="status-item">
                <strong>📝 Reference ID:</strong> ERR-<?php echo substr(md5(time()), 0, 8); ?>
            </div>
        </div>
        
        <div class="btn-group-custom">
            <a href="/" class="btn-custom btn-primary-custom">
                <i class="fas fa-home"></i> กลับไปหน้าแรก
            </a>
            <a href="javascript:location.reload()" class="btn-custom btn-secondary-custom">
                <i class="fas fa-redo"></i> ลองใหม่
            </a>
        </div>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 14px;">
            <p>หากปัญหายังคงเกิดขึ้น โปรดติดต่อ Admin และให้ Reference ID ข้างต้น</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
