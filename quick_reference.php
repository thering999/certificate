<?php
/**
 * Quick Reference - Color Codes & Design Tips
 * สามารถพิมพ์เพื่อใช้เป็นกระดาษอ้างอิง
 */
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quick Reference Card - Certificate Design</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .btn-print { display: none; }
            body { background: white; }
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .quick-ref-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            page-break-inside: avoid;
        }
        
        .card-title {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .color-palette {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .color-box {
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            color: white;
            font-weight: bold;
        }
        
        .color-info {
            font-size: 0.85rem;
            color: #666;
        }
        
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .tip-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 6px;
        }
        
        .tip-title {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .code-copy {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin: 10px 0;
            cursor: pointer;
            border: 1px solid #ddd;
        }
        
        .header-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .print-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
<div class="card-container">
    <!-- Header Banner -->
    <div class="header-banner">
        <h1><i class="fas fa-palette me-2"></i>Certificate Designer Quick Reference</h1>
        <p class="mb-0">🎨 คู่มืออ้างอิงด่วนสำหรับการออกแบบใบประกาศ</p>
    </div>
    
    <!-- Color Codes -->
    <div class="quick-ref-card">
        <h2 class="card-title"><i class="fas fa-palette me-2"></i>7 Color Palettes</h2>
        
        <div class="row">
            <!-- 1. Government -->
            <div class="col-md-6 mb-4">
                <h5>🎖️ ราชการ (Government)</h5>
                <div class="color-palette">
                    <div>
                        <div class="color-box" style="background-color: #1a237e;">
                            Navy Blue
                        </div>
                        <div class="code-copy" onclick="copyCode('#1a237e')">#1a237e</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #ffd700; color: #333;">
                            Gold
                        </div>
                        <div class="code-copy" onclick="copyCode('#ffd700')">#ffd700</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #c62828;">
                            Red
                        </div>
                        <div class="code-copy" onclick="copyCode('#c62828')">#c62828</div>
                    </div>
                </div>
                <p class="small text-muted"><strong>ฟอนต์:</strong> THSarabunNew, Tahoma</p>
            </div>
            
            <!-- 2. Modern -->
            <div class="col-md-6 mb-4">
                <h5>🚀 โมเดิร์น (Modern)</h5>
                <div class="color-palette">
                    <div>
                        <div class="color-box" style="background-color: #00bcd4;">
                            Cyan
                        </div>
                        <div class="code-copy" onclick="copyCode('#00bcd4')">#00bcd4</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #ff6b6b;">
                            Coral
                        </div>
                        <div class="code-copy" onclick="copyCode('#ff6b6b')">#ff6b6b</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #4caf50;">
                            Green
                        </div>
                        <div class="code-copy" onclick="copyCode('#4caf50')">#4caf50</div>
                    </div>
                </div>
                <p class="small text-muted"><strong>ฟอนต์:</strong> IBM Plex Sans Thai</p>
            </div>
            
            <!-- 3. Elegant -->
            <div class="col-md-6 mb-4">
                <h5>✨ หรูหรา (Elegant)</h5>
                <div class="color-palette">
                    <div>
                        <div class="color-box" style="background-color: #c9a961;">
                            Gold Accent
                        </div>
                        <div class="code-copy" onclick="copyCode('#c9a961')">#c9a961</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #2c3e50;">
                            Slate
                        </div>
                        <div class="code-copy" onclick="copyCode('#2c3e50')">#2c3e50</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #f5f5dc; color: #333; border: 1px solid #ddd;">
                            Cream
                        </div>
                        <div class="code-copy" onclick="copyCode('#f5f5dc')">#f5f5dc</div>
                    </div>
                </div>
                <p class="small text-muted"><strong>ฟอนต์:</strong> THSarabunNew</p>
            </div>
            
            <!-- 4. Education -->
            <div class="col-md-6 mb-4">
                <h5>🎓 การศึกษา (Education)</h5>
                <div class="color-palette">
                    <div>
                        <div class="color-box" style="background-color: #1565c0;">
                            Deep Blue
                        </div>
                        <div class="code-copy" onclick="copyCode('#1565c0')">#1565c0</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #0277bd;">
                            Light Blue
                        </div>
                        <div class="code-copy" onclick="copyCode('#0277bd')">#0277bd</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #0d47a1;">
                            Navy
                        </div>
                        <div class="code-copy" onclick="copyCode('#0d47a1')">#0d47a1</div>
                    </div>
                </div>
                <p class="small text-muted"><strong>ฟอนต์:</strong> Tahoma, THSarabunNew</p>
            </div>
            
            <!-- 5. Achievement -->
            <div class="col-md-6 mb-4">
                <h5>🏆 ความสำเร็จ (Achievement)</h5>
                <div class="color-palette">
                    <div>
                        <div class="color-box" style="background-color: #c62828;">
                            Red
                        </div>
                        <div class="code-copy" onclick="copyCode('#c62828')">#c62828</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #ffd700; color: #333;">
                            Gold
                        </div>
                        <div class="code-copy" onclick="copyCode('#ffd700')">#ffd700</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #b71c1c;">
                            Dark Red
                        </div>
                        <div class="code-copy" onclick="copyCode('#b71c1c')">#b71c1c</div>
                    </div>
                </div>
                <p class="small text-muted"><strong>ฟอนต์:</strong> THSarabunNew Bold</p>
            </div>
            
            <!-- 6. Creative -->
            <div class="col-md-6 mb-4">
                <h5>🌟 ศิลปะ (Creative)</h5>
                <div class="color-palette">
                    <div>
                        <div class="color-box" style="background-color: #7b1fa2;">
                            Purple
                        </div>
                        <div class="code-copy" onclick="copyCode('#7b1fa2')">#7b1fa2</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #ff6e40;">
                            Orange
                        </div>
                        <div class="code-copy" onclick="copyCode('#ff6e40')">#ff6e40</div>
                    </div>
                    <div>
                        <div class="color-box" style="background-color: #5e35b1;">
                            Deep Purple
                        </div>
                        <div class="code-copy" onclick="copyCode('#5e35b1')">#5e35b1</div>
                    </div>
                </div>
                <p class="small text-muted"><strong>ฟอนต์:</strong> IBM Plex, Kanit</p>
            </div>
        </div>
    </div>
    
    <!-- Design Tips -->
    <div class="quick-ref-card">
        <h2 class="card-title"><i class="fas fa-lightbulb me-2"></i>Design Tips & Best Practices</h2>
        
        <div class="tips-grid">
            <div class="tip-box">
                <div class="tip-title">1️⃣ สี & Contrast</div>
                <ul style="margin-bottom: 0; padding-left: 20px;">
                    <li>เลือก 3 สีหลัก</li>
                    <li>Contrast ต้องชัดเจน</li>
                    <li>ทดสอบกับ Color Blindness</li>
                </ul>
            </div>
            
            <div class="tip-box">
                <div class="tip-title">2️⃣ ฟอนต์</div>
                <ul style="margin-bottom: 0; padding-left: 20px;">
                    <li>ใช้ 2-3 ฟอนต์เท่านั้น</li>
                    <li>Heading: Bold</li>
                    <li>Body: Regular/Light</li>
                </ul>
            </div>
            
            <div class="tip-box">
                <div class="tip-title">3️⃣ ขอบ & ตกแต่ง</div>
                <ul style="margin-bottom: 0; padding-left: 20px;">
                    <li>ขอบหนา 20-30px</li>
                    <li>ลายสมมาตร</li>
                    <li>Flourish ที่มุม</li>
                </ul>
            </div>
            
            <div class="tip-box">
                <div class="tip-title">4️⃣ เค้าโครง</div>
                <ul style="margin-bottom: 0; padding-left: 20px;">
                    <li>เว้นพื้นที่ว่าง</li>
                    <li>ชื่อตรงกลาง</li>
                    <li>สัดส่วน 1/3 ด้านล่าง</li>
                </ul>
            </div>
            
            <div class="tip-box">
                <div class="tip-title">5️⃣ รูปภาพ & ไอคอน</div>
                <ul style="margin-bottom: 0; padding-left: 20px;">
                    <li>ใช้ SVG เสมอ</li>
                    <li>โลโก้ที่มุมหรือกลาง</li>
                    <li>ตรงกับธีม</li>
                </ul>
            </div>
            
            <div class="tip-box">
                <div class="tip-title">6️⃣ การพิมพ์</div>
                <ul style="margin-bottom: 0; padding-left: 20px;">
                    <li>ทดสอบ Preview</li>
                    <li>ใช้ 300 DPI</li>
                    <li>ตรวจ Margins</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Font Sizes -->
    <div class="quick-ref-card">
        <h2 class="card-title"><i class="fas fa-ruler-vertical me-2"></i>ขนาดฟอนต์แนะนำ</h2>
        
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>องค์ประกอบ</th>
                    <th>ขนาด (px)</th>
                    <th>ลักษณะ</th>
                    <th>ตัวอย่าง</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>ชื่อ (Name)</strong></td>
                    <td>32-48</td>
                    <td>Bold, Center</td>
                    <td><span style="font-size: 40px; font-weight: bold;">สมชาย ใจดี</span></td>
                </tr>
                <tr>
                    <td><strong>หัวข้อ (Title)</strong></td>
                    <td>24-32</td>
                    <td>Bold</td>
                    <td><span style="font-size: 28px; font-weight: bold;">ใบประกาศ</span></td>
                </tr>
                <tr>
                    <td><strong>อธิบาย (Description)</strong></td>
                    <td>14-18</td>
                    <td>Regular</td>
                    <td><span style="font-size: 16px;">ได้รับการยอมรับ</span></td>
                </tr>
                <tr>
                    <td><strong>รายละเอียด (Detail)</strong></td>
                    <td>10-14</td>
                    <td>Light</td>
                    <td><span style="font-size: 12px;">วันที่ / ลายเซ็น</span></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Spacing Guide -->
    <div class="quick-ref-card">
        <h2 class="card-title"><i class="fas fa-borders me-2"></i>Spacing & Layout Guide</h2>
        
        <div class="row">
            <div class="col-md-6">
                <h5>⬚ ขนาดมาตรฐาน</h5>
                <ul>
                    <li><strong>ขนาดเอกสาร:</strong> A4 (210×297 mm)</li>
                    <li><strong>Resolution:</strong> 300 DPI (สำหรับพิมพ์)</li>
                    <li><strong>Margin:</strong> 20-30 mm ทั้งสี่ด้าน</li>
                    <li><strong>Content Area:</strong> 150-250 mm</li>
                </ul>
            </div>
            
            <div class="col-md-6">
                <h5>📐 ตำแหน่งชื่อ</h5>
                <ul>
                    <li><strong>X Position:</strong> Center (105 mm)</li>
                    <li><strong>Y Position:</strong> Lower 1/3 (180-200 mm)</li>
                    <li><strong>Width:</strong> 150-200 mm</li>
                    <li><strong>Padding:</strong> ทั้งด้านบนและล่าง 10 mm</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="quick-ref-card">
        <h2 class="card-title"><i class="fas fa-link me-2"></i>Quick Navigation</h2>
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <a href="template_inspiration.php" target="_blank" class="btn btn-info w-100 mb-2">
                    <i class="fas fa-lightbulb me-1"></i>Template Inspiration
                </a>
                <small class="text-muted">ดูแนวทาง 7 สไตล์</small>
            </div>
            
            <div class="col-md-4 mb-3">
                <a href="background_templates.php" target="_blank" class="btn btn-success w-100 mb-2">
                    <i class="fas fa-images me-1"></i>Background Gallery
                </a>
                <small class="text-muted">พื้นหลังพร้อมใช้</small>
            </div>
            
            <div class="col-md-4 mb-3">
                <a href="designer.php" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-paint-brush me-1"></i>Designer
                </a>
                <small class="text-muted">ไปออกแบบ</small>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6 mb-3">
                <a href="https://www.canva.com/s/templates?query=%E0%B9%80%E0%B8%81%E0%B8%B5%E0%B8%A2%E0%B8%A3%E0%B8%95%E0%B8%B4%E0%B8%9A%E0%B8%B1%E0%B8%95%E0%B8%A3" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                    <i class="fab fa-canva me-1"></i>Canva Templates
                </a>
                <small class="text-muted">แรงบันดาลใจเพิ่มเติม</small>
            </div>
            
            <div class="col-md-6 mb-3">
                <a href="DESIGN_INSPIRATION_GUIDE.md" target="_blank" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fas fa-book me-1"></i>Full Guide
                </a>
                <small class="text-muted">คู่มือฉบับสมบูรณ์</small>
            </div>
        </div>
    </div>
    
    <!-- Print Button -->
    <div class="text-center mb-5">
        <button class="btn btn-lg btn-primary btn-print" onclick="window.print()">
            <i class="fas fa-print me-2"></i>พิมพ์ Quick Reference นี้
        </button>
    </div>
</div>

<script>
function copyCode(hex) {
    navigator.clipboard.writeText(hex).then(() => {
        alert('✓ คัดลอก ' + hex + ' เรียบร้อย');
    });
}

// Print friendly
window.addEventListener('beforeprint', function() {
    document.querySelectorAll('.card-container').forEach(el => {
        el.style.maxWidth = '100%';
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
