<?php
/**
 * Template Inspiration & Design Guides
 * Provides design ideas, color schemes, and Canva inspiration links
 * เพื่อช่วยผู้ใช้ออกแบบใบประกาศด้วยแนวทางจากศิลปินมืออาชีพ
 */
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ตัวอย่างการออกแบบพื้นหลัง 7 แบบ
$templates = [
    [
        'id' => 1,
        'name' => '🎖️ ราชการอย่างเป็นทางการ',
        'category' => 'government',
        'description' => 'ลวดลายราชการ หรูหรา คุณธรรม',
        'colors' => [
            'primary' => '#1a237e',      // Navy Blue
            'accent' => '#ffd700',        // Gold
            'text' => '#222222',
            'border' => '#c62828'         // Royal Red
        ],
        'fonts' => ['THSarabunNew', 'Tahoma'],
        'features' => [
            '✓ ตราประทับหรือสัญลักษณ์ของสถาบัน',
            '✓ ลายธปกอย่างหรูหรา',
            '✓ สีทองสำหรับเน้น',
            '✓ ขอบตกแต่งแบบดั้งเดิม'
        ],
        'canva_link' => 'https://www.canva.com/s/templates?query=เกียรติบัตรราชการ',
        'design_tips' => [
            'ใช้สีเข้มๆ (Navy, Maroon) สำหรับความเป็นทางการ',
            'เพิ่มลายตกแต่งที่วิจิตรและสมควร',
            'ใช้ฟอนต์ Serif เพื่อความสง่างาม',
            'มีพื้นที่สำหรับลายเซ็นของผู้อนุมัติ'
        ]
    ],
    [
        'id' => 2,
        'name' => '🚀 โมเดิร์นและมีชีวิตชีวา',
        'category' => 'modern',
        'description' => 'สีสันสดใส ลายเรขาคณิต น้อยคำมาก',
        'colors' => [
            'primary' => '#00bcd4',       // Cyan
            'accent' => '#ff6b6b',        // Coral
            'text' => '#333333',
            'border' => '#4caf50'         // Green
        ],
        'fonts' => ['THSarabunNew', 'IBM Plex Sans Thai'],
        'features' => [
            '✓ รูปทรงเรขาคณิตสมัยใหม่',
            '✓ สีสันสดใส และตัดกัน',
            '✓ การใช้พื้นที่ว่างอย่างสงบ',
            '✓ ไม่มีลายเยอะเกินไป'
        ],
        'canva_link' => 'https://www.canva.com/s/templates?query=modern+certificate',
        'design_tips' => [
            'เลือก 2-3 สีหลัก และใช้ให้คงที่',
            'ใช้ geometric shapes (สี่เหลี่ยม, วงกลม, เส้น)',
            'เว้นพื้นที่ว่างเพื่อให้ "หายใจได้"',
            'ใช้ฟอนต์ Clean และ Sans-serif'
        ]
    ],
    [
        'id' => 3,
        'name' => '✨ หรูหรา และประณีต',
        'category' => 'elegant',
        'description' => 'ทอง สีนวล ลายนูน ดูแพงมาก',
        'colors' => [
            'primary' => '#c9a961',       // Gold
            'accent' => '#2c3e50',        // Dark Slate
            'text' => '#1a1a1a',
            'border' => '#34495e'         // Slate
        ],
        'fonts' => ['THSarabunNew'],
        'features' => [
            '✓ ลายทองและเงินหรูหรา',
            '✓ พื้นหลังเบาบาง',
            '✓ ตัวอักษรแบบดั้งเดิมบาง',
            '✓ ขอบสไตล์ Vintage'
        ],
        'canva_link' => 'https://www.canva.com/s/templates?query=luxury+certificate',
        'design_tips' => [
            'ใช้สีทองคำ เงิน และสีขาว',
            'เพิ่ม flourish (ลายปั่น) ที่มุม',
            'ใช้ pattern ที่ละเอียด',
            'เลือกฟอนต์แบบ Serif ที่หรูหรา'
        ]
    ],
    [
        'id' => 4,
        'name' => '🎓 การศึกษา & ความรู้',
        'category' => 'education',
        'description' => 'สีน้ำเงิน หนังสือ ม้วนกระดาษ ที่ศึกษา',
        'colors' => [
            'primary' => '#1565c0',       // Deep Blue
            'accent' => '#0277bd',        // Light Blue
            'text' => '#1a1a1a',
            'border' => '#0d47a1'         // Navy
        ],
        'fonts' => ['THSarabunNew', 'Tahoma'],
        'features' => [
            '✓ ไอคอนเกี่ยวกับการศึกษา',
            '✓ ม้วนกระดาษแบบวิหารมหาวิทยาลัย',
            '✓ ม้วนโบราณที่มุม',
            '✓ วลีเกี่ยวกับความรู้และสติ'
        ],
        'canva_link' => 'https://www.canva.com/s/templates?query=education+certificate',
        'design_tips' => [
            'ใช้สีน้ำเงินเข้ม (แทน Red)',
            'เพิ่มหนังสือ ดินสอ หรือความรู้',
            'ม้วนกระดาษที่มุมก่อนรับปริญญา',
            'ใช้ฟอนต์ที่ดูเป็นทางการ'
        ]
    ],
    [
        'id' => 5,
        'name' => '🏆 ความสำเร็จและรางวัล',
        'category' => 'achievement',
        'description' => 'ดาว เหรียญ เลมอนสีผ้าแดง ความยิ่งใหญ่',
        'colors' => [
            'primary' => '#c62828',       // Red
            'accent' => '#ffd700',        // Gold
            'text' => '#ffffff',
            'border' => '#b71c1c'         // Dark Red
        ],
        'fonts' => ['THSarabunNew'],
        'features' => [
            '✓ ดาวเหรียญเหน้าปก',
            '✓ พื้นหลังสีแดงหรือทอง',
            '✓ วลีเกี่ยวกับความสำเร็จ',
            '✓ ขอบหนาแน่น'
        ],
        'canva_link' => 'https://www.canva.com/s/templates?query=award+certificate',
        'design_tips' => [
            'ใช้สีแดง ทอง และเงิน',
            'เพิ่มดาว เหรียญ หรือรางวัล',
            'พื้นหลังลาด gradient ทอง-แดง',
            'ตัวอักษรขาว หรือทอง'
        ]
    ],
    [
        'id' => 6,
        'name' => '🌟 ศิลปะและจินตนาการ',
        'category' => 'creative',
        'description' => 'หลากสี สร้างสรรค์ ศิลป์ ศิลปะทั่วไป',
        'colors' => [
            'primary' => '#7b1fa2',       // Purple
            'accent' => '#ff6e40',        // Orange
            'text' => '#333333',
            'border' => '#5e35b1'         // Deep Purple
        ],
        'fonts' => ['IBM Plex Sans Thai', 'Kanit'],
        'features' => [
            '✓ สีสันสดใสหลากหลาย',
            '✓ ศิลปะและลายเพ้นท์',
            '✓ ลวดลายวิจิตรประณีต',
            '✓ ความสร้างสรรค์'
        ],
        'canva_link' => 'https://www.canva.com/s/templates?query=creative+certificate+art',
        'design_tips' => [
            'ใช้สีม่วง นีออน หรือเรนโบว์',
            'เพิ่ม watercolor หรือ splashes',
            'ลายศิลปะวิจิตรประณีต',
            'ตัวอักษรสดใสและลาด'
        ]
    ],
    [
        'id' => 7,
        'name' => '🌍 ธรรมชาติและสิ่งแวดล้อม',
        'category' => 'nature',
        'description' => 'เขียว ใบไม้ ดอกไม้ ธรรมชาติ',
        'colors' => [
            'primary' => '#2e7d32',       // Green
            'accent' => '#81c784',        // Light Green
            'text' => '#1a1a1a',
            'border' => '#1b5e20'         // Dark Green
        ],
        'fonts' => ['THSarabunNew'],
        'features' => [
            '✓ ใบไม้และธรรมชาติ',
            '✓ พื้นหลังเขียวอ่อนนวล',
            '✓ ดอกไม้มุมมุมต่างๆ',
            '✓ วลีเกี่ยวกับความยั่งยืน'
        ],
        'canva_link' => 'https://www.canva.com/s/templates?query=nature+eco+certificate',
        'design_tips' => [
            'ใช้สีเขียวต่างระดับ',
            'เพิ่มใบไม้ หรือดอกไม้มุม',
            'พื้นหลังธรรมชาติอ่อนๆ',
            'ใช้ฟอนต์ที่รู้สึกเป็นธรรมชาติ'
        ]
    ]
];

// คำแนะนำทั่วไป
$general_tips = [
    [
        'icon' => 'fa-palette',
        'title' => 'สีและ Contrast',
        'tips' => [
            'เลือก 3 สีหลักที่กลมกลืนกัน',
            'ให้ Text และพื้นหลัง Contrast ชัดเจน',
            'ใช้เครื่องมือ Color Picker ของ Canva'
        ]
    ],
    [
        'icon' => 'fa-font',
        'title' => 'ฟอนต์ที่เหมาะสม',
        'tips' => [
            'ใช้ฟอนต์ 2-3 แบบ เท่านั้น',
            'Heading ใช้ Bold, Body ใช้ Regular',
            'ตรวจสอบให้ชัดเจน ที่ระดับ 96 DPI'
        ]
    ],
    [
        'icon' => 'fa-border',
        'title' => 'ขอบและลวดลาย',
        'tips' => [
            'ขอบหนา 20-30px สำหรับความสมดุล',
            'ใช้ลายสมมาตร',
            'เพิ่มคุณลักษณะ (corners, flourish) ด้านมุม'
        ]
    ],
    [
        'icon' => 'fa-ruler',
        'title' => 'เค้าโครงและพื้นที่',
        'tips' => [
            'เว้นพื้นที่ว่างอย่างสมดุล',
            'ตำแหน่งชื่อ ต่ำสุดที่ center ของหนึ่งในสาม',
            'เกี่ยวกับการเว้นช่องว่าง'
        ]
    ],
    [
        'icon' => 'fa-images',
        'title' => 'รูปภาพและไอคอน',
        'tips' => [
            'ใช้ SVG สำหรับคุณภาพที่ดี',
            'โลโก้ที่มุม หรือตรงกลาง',
            'ไอคอนให้เข้ากับธีม'
        ]
    ],
    [
        'icon' => 'fa-print',
        'title' => 'การพิมพ์',
        'tips' => [
            'ทดสอบในโปรแกรม Preview',
            'ใช้ 300 DPI สำหรับการพิมพ์',
            'ตรวจสอบ Margins ของเครื่องพิมพ์'
        ]
    ]
];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Template Inspiration & Design Guide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
            margin-bottom: 40px;
            border-radius: 15px;
        }
        
        .template-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        
        .template-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        
        .template-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .template-category {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .color-palette {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .color-sample {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .color-sample:hover {
            transform: scale(1.1);
        }
        
        .color-label {
            font-size: 0.75rem;
            text-align: center;
            margin-top: 5px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li::before {
            content: "✓ ";
            color: #4caf50;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .tips-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .tips-section h6 {
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .tips-list li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .tips-list li::before {
            content: "→";
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }
        
        .canva-button {
            background: #0099ff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            font-weight: bold;
        }
        
        .canva-button:hover {
            background: #0077cc;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .tip-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .tip-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .tip-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .back-button {
            margin-bottom: 20px;
        }
        
        .section-title {
            color: var(--primary);
            font-weight: bold;
            margin: 40px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary);
        }
        
        .footer-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin: 30px 0;
        }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-lightbulb me-2"></i>Template Inspiration & Design Guide
        </a>
        <div class="navbar-nav ms-auto">
            <a href="index.php" class="nav-link"><i class="fas fa-home me-1"></i>หน้าหลัก</a>
            <a href="designer.php" class="nav-link"><i class="fas fa-paint-brush me-1"></i>Designer</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- Back Button -->
    <a href="designer.php" class="btn btn-light back-button">
        <i class="fas fa-arrow-left me-2"></i>กลับไปที่ Designer
    </a>
    
    <!-- Hero Section -->
    <div class="hero-section">
        <h1><i class="fas fa-palette me-2"></i>ไอเดียการออกแบบใบเกียรติบัตร</h1>
        <p class="lead">ค้นหาแรงบันดาลใจจากแม่แบบมืออาชีพ และเรียนรู้วิธีการออกแบบด้วยตัวของคุณเอง</p>
        <p class="small text-white-50">✨ เลือกแบบที่ชอบ → ไปที่ Canva ดูตัวอย่าง → ปรับแต่งในระบบของเรา</p>
    </div>
    
    <!-- Template Examples -->
    <h2 class="section-title">📋 ตัวอย่าง Template แบบต่างๆ (7 สไตล์)</h2>
    
    <div class="row">
        <?php foreach ($templates as $template): ?>
        <div class="col-lg-6 mb-4">
            <div class="template-card">
                <div class="template-header">
                    <div class="template-name"><?= $template['name'] ?></div>
                    <div class="template-category">
                        <small><?= $template['description'] ?></small>
                    </div>
                </div>
                
                <div class="p-4">
                    <!-- Color Palette -->
                    <h6><i class="fas fa-palette me-2"></i>สีเสนอแนะ</h6>
                    <div class="color-palette">
                        <?php foreach ($template['colors'] as $color_name => $color_hex): ?>
                        <div style="text-align: center;">
                            <div class="color-sample" style="background-color: <?= $color_hex ?>;" 
                                 title="<?= $color_name ?>: <?= $color_hex ?>"
                                 onclick="copyToClipboard('<?= $color_hex ?>')"></div>
                            <div class="color-label"><?= strtoupper(str_replace('_', ' ', $color_name)) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted">💡 คลิกสีเพื่อคัดลอก Hex Code</small>
                    
                    <!-- Fonts -->
                    <h6 class="mt-3"><i class="fas fa-font me-2"></i>ฟอนต์เสนอแนะ</h6>
                    <div>
                        <?php foreach ($template['fonts'] as $font): ?>
                        <span class="badge bg-light text-dark me-2 mb-2"><?= $font ?></span>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Features -->
                    <h6 class="mt-3"><i class="fas fa-star me-2"></i>คุณลักษณะ</h6>
                    <ul class="feature-list">
                        <?php foreach ($template['features'] as $feature): ?>
                        <li><?= $feature ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <!-- Design Tips -->
                    <div class="tips-section">
                        <h6><i class="fas fa-lightbulb me-2"></i>เคล็ดลับการออกแบบ</h6>
                        <ul class="tips-list">
                            <?php foreach ($template['design_tips'] as $tip): ?>
                            <li><?= $tip ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Canva Link -->
                    <div class="mt-3 text-center">
                        <a href="<?= $template['canva_link'] ?>" target="_blank" class="canva-button">
                            <i class="fab fa-canva me-2"></i>ดูตัวอย่างใน Canva
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- General Design Tips -->
    <h2 class="section-title">🎨 เคล็ดลับการออกแบบทั่วไป</h2>
    
    <div class="row">
        <?php foreach ($general_tips as $tip): ?>
        <div class="col-lg-4 col-md-6">
            <div class="tip-card text-center">
                <div class="tip-icon">
                    <i class="fas <?= $tip['icon'] ?>"></i>
                </div>
                <h5><?= $tip['title'] ?></h5>
                <ul class="tips-list text-start small">
                    <?php foreach ($tip['tips'] as $item): ?>
                    <li><?= $item ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Step by Step Guide -->
    <h2 class="section-title">📖 ขั้นตอนการใช้งาน</h2>
    
    <div class="row">
        <div class="col-md-12">
            <div class="tip-card">
                <ol class="list-group list-group-numbered">
                    <li class="list-group-item">
                        <strong>ค้นหาแรงบันดาลใจ</strong><br>
                        เลือก Template ที่ชอบจากด้านบน และคลิก "ดูตัวอย่างใน Canva"
                    </li>
                    <li class="list-group-item">
                        <strong>ศึกษาและจดบันทึก</strong><br>
                        ดูตัวอย่างใน Canva และจดบันทึกว่า:
                        <ul class="mt-2 mb-0">
                            <li>สีไหนใช้งาน</li>
                            <li>ลายเพ้นท์หรือไอคอนแบบไหน</li>
                            <li>เค้าโครงว่างเหว่</li>
                        </ul>
                    </li>
                    <li class="list-group-item">
                        <strong>ปรับแต่งในระบบ</strong><br>
                        ไปที่ <a href="designer.php" class="btn btn-sm btn-primary">Designer</a> 
                        และใช้จดบันทึกมาปรับแต่ง
                    </li>
                    <li class="list-group-item">
                        <strong>ทดสอบและส่งออก</strong><br>
                        ดูตัวอย่างและส่งออก PNG/PDF เพื่อตรวจสอบผลลัพธ์
                    </li>
                </ol>
            </div>
        </div>
    </div>
    
    <!-- Footer Note -->
    <div class="footer-note">
        <h5><i class="fas fa-info-circle me-2"></i>ข้อมูลเพิ่มเติม</h5>
        <ul class="mb-0">
            <li>💾 <strong>บันทึกการตั้งค่า</strong> - ระบบจะจำค่าตั้งค่าสีและฟอนต์ของคุณ</li>
            <li>🎨 <strong>Color Picker</strong> - ใช้เครื่องมือ Color Picker เพื่อเลือกสีจากรูปภาพ</li>
            <li>📸 <strong>Upload Background</strong> - อัปโหลดพื้นหลังของคุณเองสำหรับ customize มากขึ้น</li>
            <li>🔗 <strong>ลิงก์ Canva</strong> - คลิกที่ปุ่ม "ดูตัวอย่างใน Canva" เพื่อดูตัวอย่างเพิ่มเติม</li>
        </ul>
    </div>
    
    <!-- Action Buttons -->
    <div class="text-center my-40">
        <a href="designer.php" class="btn btn-primary btn-lg me-2">
            <i class="fas fa-paint-brush me-2"></i>ไปที่ Designer เลย
        </a>
        <a href="template_manage.php" class="btn btn-outline-primary btn-lg">
            <i class="fas fa-folder-open me-2"></i>จัดการ Templates
        </a>
    </div>
</div>

<script>
function copyToClipboard(hex) {
    navigator.clipboard.writeText(hex).then(() => {
        alert('✓ คัดลอก ' + hex + ' เรียบร้อย');
    });
}

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth' });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>
