<?php
require_once 'db.php';
require_once 'assets/error_handler.php';

session_start();

// Check if this page is accessed after successful registration
$success_message = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']); // Clear after displaying

if (!$success_message) {
    header('Location: register.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิกเสร็จแล้ว - Certificate Designer</title>
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
        .success-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .success-icon {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 0.6s ease-in-out;
        }
        .success-title {
            color: #28a745;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .success-subtitle {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 30px;
        }
        .steps-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .step:last-child {
            margin-bottom: 0;
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .step-content h5 {
            color: #333;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .step-content p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 30px;
            text-align: left;
        }
        .info-box i {
            color: #1976d2;
            margin-right: 10px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: bold;
            padding: 12px 40px;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
        .features-list {
            background: #f0f4ff;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .features-list h6 {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #555;
        }
        .feature-item:last-child {
            margin-bottom: 0;
        }
        .feature-item i {
            color: #764ba2;
            margin-right: 10px;
            width: 20px;
        }
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .countdown {
            font-size: 14px;
            color: #999;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <div class="success-title">สมัครสมาชิกเสร็จแล้ว!</div>
        <div class="success-subtitle">ยินดีต้อนรับสู่ระบบสร้างใบประกาศ</div>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>บัญชีของคุณพร้อมใช้งานแล้ว</strong>
            <p style="margin-top: 5px;">กรุณาเข้าสู่ระบบด้วยชื่อผู้ใช้และรหัสผ่านที่ที่คุณเพิ่งสมัครไป</p>
        </div>
        
        <div class="steps-container">
            <h5 style="color: #667eea; margin-bottom: 20px;">
                <i class="fas fa-tasks"></i> ขั้นตอนต่อไป
            </h5>
            
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h5>เข้าสู่ระบบ</h5>
                    <p>กดปุ่ม "เข้าสู่ระบบ" ด้านล่างเพื่อเข้าสู่บัญชีของคุณ</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h5>ไปที่ Certificate Generator</h5>
                    <p>หลังจากเข้าสู่ระบบ คลิก "Certificate Designer" เพื่อเริ่มสร้างใบประกาศ</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h5>ออกแบบใบประกาศของคุณ</h5>
                    <p>เลือกแม่แบบ อัปโหลดพื้นหลัง เพิ่มข้อความ และปรับแต่งตามต้องการ</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h5>ส่งออกใบประกาศ</h5>
                    <p>กดปุ่ม "ส่งออก PNG" เพื่อดาวน์โหลดใบประกาศของคุณในรูปแบบ PNG</p>
                </div>
            </div>
        </div>
        
        <div class="features-list">
            <h6><i class="fas fa-star"></i> คุณสามารถใช้งาน:</h6>
            <div class="feature-item">
                <i class="fas fa-check"></i>
                <span>แม่แบบพร้อมใช้ 9 แบบที่หลากหลาย</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-check"></i>
                <span>อัปโหลดพื้นหลังและสไลด์โดยอิสระ</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-check"></i>
                <span>ลาก-วางเพื่อปรับตำแหน่งข้อความ</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-check"></i>
                <span>ส่งออก PNG คุณภาพสูง</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-check"></i>
                <span>บันทึกประวัติการสร้าง</span>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="login.php" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบตอนนี้
            </a>
        </div>
        
        <p class="countdown">
            <i class="fas fa-clock"></i> 
            <span id="redirectCountdown"></span>
        </p>
    </div>
</div>

<script>
// Auto-redirect after 10 seconds
let seconds = 10;
function updateCountdown() {
    const element = document.getElementById('redirectCountdown');
    if (seconds > 0) {
        element.textContent = `จะนำไปที่หน้าเข้าสู่ระบบใน ${seconds} วินาที...`;
        seconds--;
        setTimeout(updateCountdown, 1000);
    } else {
        window.location.href = 'login.php';
    }
}

// Start countdown
updateCountdown();

// Allow clicking link to go immediately
document.querySelector('a.btn-login').addEventListener('click', function() {
    clearTimeout();
});
</script>
</body>
</html>
