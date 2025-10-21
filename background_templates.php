<?php
/**
 * Background Template Gallery with Preview
 * แสดงตัวอย่าง Background ขนาดจิ๋วและสามารถใช้เป็น Preset
 */
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

// Background Presets - สีและลายเพ้นท์
$background_presets = [
    [
        'id' => 'gov_formal',
        'name' => '🎖️ ราชการอย่างเป็นทางการ',
        'type' => 'gradient',
        'color1' => '#1a237e',
        'color2' => '#283593',
        'border_style' => 'double',
        'border_color' => '#ffd700',
        'border_width' => 25,
        'accent_color' => '#c62828',
        'pattern' => 'ornament'
    ],
    [
        'id' => 'modern_tech',
        'name' => '🚀 โมเดิร์นและเทคโนโลยี',
        'type' => 'gradient',
        'color1' => '#00bcd4',
        'color2' => '#0097a7',
        'border_style' => 'solid',
        'border_color' => '#ff6b6b',
        'border_width' => 15,
        'accent_color' => '#4caf50',
        'pattern' => 'geometric'
    ],
    [
        'id' => 'elegant_luxury',
        'name' => '✨ หรูหราและประณีต',
        'type' => 'gradient',
        'color1' => '#f5f5dc',
        'color2' => '#fffacd',
        'border_style' => 'double',
        'border_color' => '#c9a961',
        'border_width' => 20,
        'accent_color' => '#2c3e50',
        'pattern' => 'damask'
    ],
    [
        'id' => 'education_blue',
        'name' => '🎓 การศึกษาและความรู้',
        'type' => 'gradient',
        'color1' => '#1565c0',
        'color2' => '#0d47a1',
        'border_style' => 'solid',
        'border_color' => '#fff',
        'border_width' => 15,
        'accent_color' => '#0277bd',
        'pattern' => 'scroll'
    ],
    [
        'id' => 'achievement_gold',
        'name' => '🏆 ความสำเร็จและรางวัล',
        'type' => 'gradient',
        'color1' => '#c62828',
        'color2' => '#b71c1c',
        'border_style' => 'double',
        'border_color' => '#ffd700',
        'border_width' => 25,
        'accent_color' => '#fff',
        'pattern' => 'star'
    ],
    [
        'id' => 'creative_art',
        'name' => '🌟 ศิลปะและจินตนาการ',
        'type' => 'gradient',
        'color1' => '#7b1fa2',
        'color2' => '#5e35b1',
        'border_style' => 'dashed',
        'border_color' => '#ff6e40',
        'border_width' => 15,
        'accent_color' => '#4caf50',
        'pattern' => 'splatter'
    ],
    [
        'id' => 'nature_eco',
        'name' => '🌍 ธรรมชาติและสิ่งแวดล้อม',
        'type' => 'gradient',
        'color1' => '#2e7d32',
        'color2' => '#1b5e20',
        'border_style' => 'solid',
        'border_color' => '#81c784',
        'border_width' => 15,
        'accent_color' => '#4caf50',
        'pattern' => 'leaf'
    ]
];

if ($action === 'list') {
    header('Content-Type: application/json');
    echo json_encode($background_presets);
    exit;
}

if ($action === 'preview') {
    $preset_id = $_GET['id'] ?? '';
    $preset = null;
    
    foreach ($background_presets as $p) {
        if ($p['id'] === $preset_id) {
            $preset = $p;
            break;
        }
    }
    
    if (!$preset) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    
    // Generate SVG preview
    $svg = generateBackgroundPreview($preset);
    header('Content-Type: image/svg+xml');
    echo $svg;
    exit;
}

function generateBackgroundPreview($preset) {
    $width = 600;
    $height = 400;
    $border = $preset['border_width'];
    
    // Create gradient
    $gradient_id = 'grad_' . $preset['id'];
    
    $svg = <<<SVG
<svg width="$width" height="$height" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="$gradient_id" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:{$preset['color1']};stop-opacity:1" />
            <stop offset="100%" style="stop-color:{$preset['color2']};stop-opacity:1" />
        </linearGradient>
        
        <!-- Patterns -->
        <pattern id="ornament" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
            <circle cx="10" cy="10" r="2" fill="{$preset['accent_color']}" opacity="0.1"/>
        </pattern>
        
        <pattern id="geometric" x="0" y="0" width="30" height="30" patternUnits="userSpaceOnUse">
            <rect x="5" y="5" width="10" height="10" fill="{$preset['accent_color']}" opacity="0.05"/>
        </pattern>
    </defs>
    
    <!-- Background Gradient -->
    <rect width="$width" height="$height" fill="url(#$gradient_id)"/>
    
    <!-- Pattern Overlay (optional) -->
    <rect width="$width" height="$height" fill="url(#ornament)" opacity="0.3"/>
    
    <!-- Border -->
    <rect x="$border" y="$border" width="{$width - 2*$border}" height="{$height - 2*$border}" 
          fill="none" stroke="{$preset['border_color']}" stroke-width="3" stroke-dasharray="5,5" opacity="0.5"/>
    
    <!-- Inner Border -->
    <rect x="{$border + 5}" y="{$border + 5}" width="{$width - 2*$border - 10}" height="{$height - 2*$border - 10}" 
          fill="none" stroke="{$preset['border_color']}" stroke-width="1" opacity="0.3"/>
    
    <!-- Sample Text -->
    <text x="{$width/2}" y="{$height/2}" font-family="THSarabunNew, sans-serif" font-size="32" 
          font-weight="bold" text-anchor="middle" fill="{$preset['accent_color']}" opacity="0.8">
          Preview
    </text>
</svg>
SVG;
    
    return $svg;
}

?>
<?php if ($action === ''): ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Background Template Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 30px 20px;
        }
        
        .gallery-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-title {
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .template-preview-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .template-preview-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        
        .preview-image {
            width: 100%;
            height: 250px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .preview-image img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .template-info {
            padding: 15px;
        }
        
        .template-name {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .template-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            font-size: 0.85rem;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 6px;
            border-left: 3px solid var(--primary);
        }
        
        .color-box {
            width: 25px;
            height: 25px;
            display: inline-block;
            border-radius: 4px;
            border: 1px solid #ddd;
            vertical-align: middle;
            margin-right: 5px;
        }
        
        .button-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-use {
            flex: 1;
            min-width: 120px;
        }
        
        @media (max-width: 768px) {
            .template-preview-card {
                margin-bottom: 20px;
            }
            
            .template-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="gallery-container">
    <h1 class="page-title">
        <i class="fas fa-images me-2"></i>Background Template Gallery
    </h1>
    
    <div class="row">
        <?php foreach ($background_presets as $preset): ?>
        <div class="col-lg-6 mb-4">
            <div class="template-preview-card">
                <div class="preview-image">
                    <img src="?action=preview&id=<?= $preset['id'] ?>" alt="<?= $preset['name'] ?>">
                </div>
                
                <div class="template-info">
                    <div class="template-name"><?= $preset['name'] ?></div>
                    
                    <div class="template-details">
                        <div class="detail-item">
                            <strong>สี Primary:</strong><br>
                            <div class="color-box" style="background-color: <?= $preset['color1'] ?>"></div>
                            <?= $preset['color1'] ?>
                        </div>
                        
                        <div class="detail-item">
                            <strong>สี Secondary:</strong><br>
                            <div class="color-box" style="background-color: <?= $preset['color2'] ?>"></div>
                            <?= $preset['color2'] ?>
                        </div>
                        
                        <div class="detail-item">
                            <strong>ขอบ:</strong><br>
                            <?= $preset['border_width'] ?>px <?= ucfirst($preset['border_style']) ?>
                        </div>
                        
                        <div class="detail-item">
                            <strong>Pattern:</strong><br>
                            <?= ucfirst($preset['pattern']) ?>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <a href="designer.php?preset=<?= $preset['id'] ?>" class="btn btn-primary btn-sm btn-use">
                            <i class="fas fa-check me-1"></i>ใช้ Template นี้
                        </a>
                        <button class="btn btn-outline-primary btn-sm" onclick="copyColors('<?= $preset['color1'] ?>', '<?= $preset['color2'] ?>')">
                            <i class="fas fa-copy me-1"></i>คัดลอกสี
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-40">
        <a href="designer.php" class="btn btn-lg btn-primary me-2">
            <i class="fas fa-paint-brush me-2"></i>ไปที่ Designer
        </a>
        <a href="template_inspiration.php" class="btn btn-lg btn-outline-primary">
            <i class="fas fa-book me-2"></i>ดูไอเดีย & แนวทาง
        </a>
    </div>
</div>

<script>
function copyColors(color1, color2) {
    const text = `Primary: ${color1}\nSecondary: ${color2}`;
    navigator.clipboard.writeText(text).then(() => {
        alert('✓ คัดลอกสีเรียบร้อย\n' + text);
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
