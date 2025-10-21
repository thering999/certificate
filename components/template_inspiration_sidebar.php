<?php
/**
 * Template Sidebar Component
 * คอมโพเนนต์แสดง Quick Tips และลิงก์ไปยัง Template Inspiration
 * ใช้งานใน designer.php
 */
?>
<div class="design-inspiration-sidebar">
    <!-- Quick Access Button -->
    <div class="inspiration-section">
        <h5><i class="fas fa-lightbulb me-2"></i>ไอเดีย</h5>
        <a href="template_inspiration.php" class="btn btn-outline-primary w-100 mb-2" target="_blank">
            <i class="fas fa-book me-1"></i>ดูแนวทาง &ไอเดีย
        </a>
        <p class="small text-muted">เรียนรู้จากตัวอย่าง 7 สไตล์ และเชื่อมโยงกับ Canva</p>
    </div>
    
    <!-- Color Tips -->
    <div class="inspiration-section">
        <h6><i class="fas fa-palette me-2"></i>เคล็ดลับสี</h6>
        <div class="color-tips-container">
            <div class="color-tip">
                <strong style="color: #1a237e;">🎖️ ราชการ:</strong>
                Navy Blue (#1a237e) + Gold (#ffd700)
            </div>
            <div class="color-tip">
                <strong style="color: #00bcd4;">🚀 โมเดิร์น:</strong>
                Cyan (#00bcd4) + Coral (#ff6b6b)
            </div>
            <div class="color-tip">
                <strong style="color: #c9a961;">✨ หรูหรา:</strong>
                Gold (#c9a961) + Slate (#2c3e50)
            </div>
        </div>
    </div>
    
    <!-- Font Tips -->
    <div class="inspiration-section">
        <h6><i class="fas fa-font me-2"></i>ฟอนต์แนะนำ</h6>
        <ul class="small">
            <li><strong>ราชการ:</strong> THSarabunNew, Tahoma</li>
            <li><strong>โมเดิร์น:</strong> IBM Plex Sans Thai</li>
            <li><strong>หรูหรา:</strong> THSarabunNew (Serif)</li>
        </ul>
    </div>
    
    <!-- Design Checklist -->
    <div class="inspiration-section">
        <h6><i class="fas fa-check-square me-2"></i>Checklist</h6>
        <div class="form-check form-check-sm">
            <input type="checkbox" class="form-check-input" id="check1">
            <label class="form-check-label" for="check1">
                3 สีหลักเลือกแล้ว
            </label>
        </div>
        <div class="form-check form-check-sm">
            <input type="checkbox" class="form-check-input" id="check2">
            <label class="form-check-label" for="check2">
                ฟอนต์เลือกแล้ว
            </label>
        </div>
        <div class="form-check form-check-sm">
            <input type="checkbox" class="form-check-input" id="check3">
            <label class="form-check-label" for="check3">
                มี Contrast ชัดเจน
            </label>
        </div>
        <div class="form-check form-check-sm">
            <input type="checkbox" class="form-check-input" id="check4">
            <label class="form-check-label" for="check4">
                ตรวจสอบพิมพ์
            </label>
        </div>
    </div>
    
    <!-- Canva Inspiration -->
    <div class="inspiration-section bg-info bg-opacity-10">
        <h6><i class="fab fa-canva me-2"></i>Canva Templates</h6>
        <p class="small mb-2">ค้นหาแรงบันดาลใจที่:</p>
        <a href="https://www.canva.com/s/templates?query=%E0%B9%80%E0%B8%81%E0%B8%B5%E0%B8%A2%E0%B8%A3%E0%B8%95%E0%B8%B4%E0%B8%9A%E0%B8%B1%E0%B8%95%E0%B8%A3" 
           target="_blank" class="btn btn-sm btn-outline-info w-100">
            <i class="fas fa-external-link-alt me-1"></i>เกียรติบัตร
        </a>
    </div>
    
    <!-- Quick Links -->
    <div class="inspiration-section">
        <h6><i class="fas fa-link me-2"></i>ลิงก์ด่วน</h6>
        <a href="#" class="btn btn-sm btn-outline-secondary w-100 mb-1" onclick="showColorPicker()">
            <i class="fas fa-eyedropper me-1"></i>Color Picker
        </a>
        <a href="template_manage.php" class="btn btn-sm btn-outline-secondary w-100 mb-1">
            <i class="fas fa-folder-open me-1"></i>จัดการ Template
        </a>
        <a href="#" class="btn btn-sm btn-outline-secondary w-100" onclick="downloadGuide()">
            <i class="fas fa-download me-1"></i>ดาวน์โหลด Guide PDF
        </a>
    </div>
</div>

<style>
.design-inspiration-sidebar {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.inspiration-section {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.inspiration-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.inspiration-section h5,
.inspiration-section h6 {
    color: #667eea;
    font-weight: bold;
    margin-bottom: 10px;
    font-size: 0.95rem;
}

.color-tips-container {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 10px;
}

.color-tip {
    padding: 8px;
    border-left: 3px solid #667eea;
    margin-bottom: 8px;
    font-size: 0.85rem;
    background: white;
    border-radius: 4px;
}

.color-tip:last-child {
    margin-bottom: 0;
}

.form-check-sm {
    font-size: 0.85rem;
    margin-bottom: 8px;
}

@media (max-width: 768px) {
    .design-inspiration-sidebar {
        margin-top: 15px;
    }
}
</style>

<script>
function showColorPicker() {
    alert('🎨 Color Picker Feature\n\nใช้เครื่องมือหยิบสีจาก: Color Input หรือ Eyedropper Tool ในเบราว์เซอร์');
}

function downloadGuide() {
    alert('📥 Guide PDF จะถูกดาวน์โหลดเร็วๆ นี้');
    // TODO: Generate and download PDF guide
}
</script>
