<?php
/**
 * Enhanced Template Gallery System
 * Provides beautiful template previews and management
 * 
 * @author HDC AI System
 * @version 1.0.0
 */

// Ensure image adapter is loaded
if (!function_exists('imagecreatefromjpeg')) {
    require_once 'image_adapter.class.php';
}

class TemplateGallery {
    
    private $db;
    private $templates_dir = 'assets/templates/';
    private $previews_dir = 'assets/templates/previews/';
    
    public function __construct($db) {
        $this->db = $db;
        $this->ensureDirectories();
    }
    
    /**
     * Ensure required directories exist
     */
    private function ensureDirectories() {
        if (!is_dir($this->templates_dir)) {
            @mkdir($this->templates_dir, 0755, true);
        }
        if (!is_dir($this->previews_dir)) {
            @mkdir($this->previews_dir, 0755, true);
        }
    }
    
    /**
     * Get all template categories with templates
     * 
     * @return array Categories with templates
     */
    public function getTemplatesByCategory() {
        $query = "SELECT id, name, category, preview_image, description FROM templates ORDER BY category, name";
        $result = $this->db->query($query);
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            $category = $row['category'] ?? 'อื่นๆ';
            if (!isset($templates[$category])) {
                $templates[$category] = [];
            }
            
            $templates[$category][] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'preview_image' => $row['preview_image'] ?? 'placeholder.png',
                'description' => $row['description'] ?? '',
                'category' => $category
            ];
        }
        
        return $templates;
    }
    
    /**
     * Get featured templates (popular or recent)
     * 
     * @param int $limit Number of templates to return
     * @return array Featured templates
     */
    public function getFeaturedTemplates($limit = 6) {
        $query = "SELECT id, name, category, preview_image, description FROM templates 
                  WHERE is_featured = 1 OR created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                  ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            $templates[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'preview_image' => $row['preview_image'],
                'description' => $row['description'],
                'category' => $row['category']
            ];
        }
        
        return $templates;
    }
    
    /**
     * Get template gallery HTML (for modal display)
     * 
     * @return string HTML for gallery display
     */
    public function getGalleryHTML() {
        $templates = $this->getTemplatesByCategory();
        
        ob_start();
        ?>
        <!-- Template Gallery Modal -->
        <div class="modal fade" id="templateGalleryModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-images"></i> แกลเลอรี่เทมเพลต
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <ul class="nav nav-tabs mb-4" role="tablist">
                            <?php foreach (array_keys($templates) as $category): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo $category === 'ราชการ' ? 'active' : ''; ?>" 
                                        id="tab-<?php echo sanitize($category); ?>" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#content-<?php echo sanitize($category); ?>" 
                                        type="button" role="tab">
                                    <?php echo htmlspecialchars($category); ?>
                                    <span class="badge bg-primary"><?php echo count($templates[$category]); ?></span>
                                </button>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="tab-content">
                            <?php foreach ($templates as $category => $category_templates): ?>
                            <div class="tab-pane fade <?php echo $category === 'ราชการ' ? 'show active' : ''; ?>" 
                                 id="content-<?php echo sanitize($category); ?>" role="tabpanel">
                                
                                <div class="row g-3">
                                    <?php foreach ($category_templates as $template): ?>
                                    <div class="col-md-4 col-lg-3">
                                        <div class="card template-card h-100 cursor-pointer transition-all hover-shadow" 
                                             onclick="selectTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['name']); ?>')">
                                            
                                            <!-- Template Preview Image -->
                                            <div class="position-relative template-preview">
                                                <img src="<?php echo htmlspecialchars($template['preview_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($template['name']); ?>"
                                                     class="card-img-top" 
                                                     style="height: 200px; object-fit: cover;">
                                                
                                                <!-- Overlay on hover -->
                                                <div class="overlay position-absolute w-100 h-100 d-flex align-items-center justify-content-center" 
                                                     style="top: 0; background: rgba(0,0,0,0.5); opacity: 0; transition: opacity 0.3s;">
                                                    <div class="text-center">
                                                        <i class="fas fa-check-circle text-white" style="font-size: 2rem;"></i>
                                                        <p class="text-white mt-2 mb-0">เลือก</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Card Body -->
                                            <div class="card-body">
                                                <h6 class="card-title text-truncate">
                                                    <?php echo htmlspecialchars($template['name']); ?>
                                                </h6>
                                                
                                                <?php if ($template['description']): ?>
                                                <p class="card-text small text-muted text-truncate">
                                                    <?php echo htmlspecialchars($template['description']); ?>
                                                </p>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex gap-2 mt-2">
                                                    <span class="badge bg-secondary flex-grow-1 text-center">
                                                        <?php echo htmlspecialchars($template['category']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .template-preview {
            overflow: hidden;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        
        .template-preview:hover .overlay {
            opacity: 1;
        }
        
        .template-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #e9ecef;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .cursor-pointer {
            cursor: pointer;
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get quick template selector HTML (compact version)
     * Shows featured templates
     * 
     * @return string HTML for quick selector
     */
    public function getQuickSelectorHTML() {
        $featured = $this->getFeaturedTemplates(8);
        
        ob_start();
        ?>
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center gap-3">
                        <h6 class="mb-0">เทมเพลตยอดนิยม:</h6>
                        <div class="template-quick-selector d-flex gap-2" style="overflow-x: auto; flex-wrap: nowrap;">
                            <?php foreach ($featured as $template): ?>
                            <div class="template-quick-item flex-shrink-0" 
                                 onclick="selectTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['name']); ?>')"
                                 title="<?php echo htmlspecialchars($template['name']); ?>">
                                <img src="<?php echo htmlspecialchars($template['preview_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($template['name']); ?>"
                                     class="img-thumbnail"
                                     style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;">
                                <small class="d-block text-center mt-1 text-truncate" style="width: 80px;">
                                    <?php echo htmlspecialchars(substr($template['name'], 0, 10)); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#templateGalleryModal">
                            ดูเพิ่มเติม
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .template-quick-selector {
            padding: 10px 0;
        }
        
        .template-quick-item:hover {
            opacity: 0.8;
        }
        
        .img-thumbnail {
            transition: transform 0.2s;
        }
        
        .img-thumbnail:hover {
            transform: scale(1.05);
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Generate template preview image
     * Creates a thumbnail from template file
     * 
     * @param int $template_id Template ID
     * @param string $template_path Path to template file
     * @return string Path to created preview
     */
    public function generatePreview($template_id, $template_path) {
        if (!file_exists($template_path)) {
            return 'assets/images/placeholder.png';
        }
        
        try {
            // Create image from template
            $ext = strtolower(pathinfo($template_path, PATHINFO_EXTENSION));
            
            if ($ext === 'jpg' || $ext === 'jpeg') {
                $image = imagecreatefromjpeg($template_path);
            } elseif ($ext === 'png') {
                $image = imagecreatefrompng($template_path);
            } else {
                return 'assets/images/placeholder.png';
            }
            
            if (!$image) {
                return 'assets/images/placeholder.png';
            }
            
            // Resize to thumbnail size (200x200)
            $thumbnail = imagecreatetruecolor(200, 200);
            $width = imagesx($image);
            $height = imagey($image);
            
            // Calculate dimensions to maintain aspect ratio
            if ($width > $height) {
                $src_width = $height;
                $src_height = $height;
                $src_x = ($width - $height) / 2;
                $src_y = 0;
            } else {
                $src_width = $width;
                $src_height = $width;
                $src_x = 0;
                $src_y = ($height - $width) / 2;
            }
            
            imagecopyresampled($thumbnail, $image, 0, 0, $src_x, $src_y, 200, 200, $src_width, $src_height);
            
            // Save preview
            $preview_path = $this->previews_dir . 'template_' . $template_id . '.png';
            imagepng($thumbnail, $preview_path);
            
            imagedestroy($image);
            imagedestroy($thumbnail);
            
            return $preview_path;
        } catch (Exception $e) {
            return 'assets/images/placeholder.png';
        }
    }
}

/**
 * Helper function to sanitize category names for HTML IDs
 */
function sanitize($string) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $string);
}
?>
