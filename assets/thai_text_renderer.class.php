<?php
/**
 * Thai Text Renderer Class
 * Handles Thai language text rendering in images
 * Fixes encoding issues and provides beautiful Thai text support
 * 
 * @author HDC AI System
 * @version 1.0.0
 */

class ThaiTextRenderer {
    
    private $font_path = 'assets/fonts/Sarabun.ttf';
    private $default_font_size = 32;
    private $default_color = [26, 35, 126]; // Dark blue
    
    public function __construct($font_path = null) {
        if ($font_path && file_exists($font_path)) {
            $this->font_path = $font_path;
        } elseif (file_exists('assets/fonts/Sarabun.ttf')) {
            $this->font_path = 'assets/fonts/Sarabun.ttf';
        }
    }
    
    /**
     * Draw Thai text on image using TTF font
     * 
     * @param resource $image GD image resource
     * @param string $text Thai text to render
     * @param int $x X coordinate
     * @param int $y Y coordinate
     * @param int $font_size Font size
     * @param array $color RGB color array [r, g, b]
     * @param string $align Text alignment (left, center, right)
     * @return bool Success status
     */
    public function drawThaiText(&$image, $text, $x, $y, $font_size = null, $color = null, $align = 'left') {
        if (!$image) {
            return false;
        }
        
        $font_size = $font_size ?? $this->default_font_size;
        $color = $color ?? $this->default_color;
        
        // Ensure text is UTF-8 encoded
        $text = $this->ensureUtf8($text);
        
        // Allocate color
        $text_color = imagecolorallocate($image, $color[0], $color[1], $color[2]);
        
        // Check if imagettftext is available
        if (!function_exists('imagettftext')) {
            // Fallback to imagestring if imagettftext not available
            return $this->drawFallbackText($image, $text, $x, $y, $font_size, $color);
        }
        
        // Check if font file exists
        if (!file_exists($this->font_path)) {
            // Fallback to imagestring if font not available
            return $this->drawFallbackText($image, $text, $x, $y, $font_size, $color);
        }
        
        // Calculate position based on alignment
        if ($align !== 'left') {
            try {
                $bbox = @imagettfbbox($font_size, 0, $this->font_path, $text);
                if ($bbox !== false) {
                    $text_width = $bbox[2] - $bbox[0];
                    $image_width = imagesx($image);
                    
                    if ($align === 'center') {
                        $x = (int)(($image_width - $text_width) / 2);
                    } elseif ($align === 'right') {
                        $x = (int)($image_width - $text_width - 50);
                    }
                }
            } catch (Exception $e) {
                // Fallback if imagettfbbox fails
                return $this->drawFallbackText($image, $text, $x, $y, $font_size, $color);
            }
        }
        
        // Draw text using TTF font
        try {
            $result = @imagettftext($image, (int)$font_size, 0, (int)$x, (int)$y, $text_color, $this->font_path, $text);
            return ($result !== false);
        } catch (Exception $e) {
            // Fallback if imagettftext fails
            return $this->drawFallbackText($image, $text, (int)$x, (int)$y, $font_size, $color);
        }
    }
    
    /**
     * Draw multi-line Thai text
     * 
     * @param resource $image GD image resource
     * @param string $text Thai text with newlines
     * @param int $x X coordinate
     * @param int $y Y coordinate
     * @param int $font_size Font size
     * @param array $color RGB color array
     * @param int $line_spacing Line spacing in pixels
     * @return bool Success status
     */
    public function drawMultilineThaiText(&$image, $text, $x, $y, $font_size = null, $color = null, $line_spacing = 10) {
        if (!$image) {
            return false;
        }
        
        $lines = explode("\n", $text);
        $current_y = $y;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $this->drawThaiText($image, $line, $x, $current_y, $font_size, $color, 'left');
                $current_y += $font_size + $line_spacing;
            }
        }
        
        return true;
    }
    
    /**
     * Ensure text is UTF-8 encoded
     * 
     * @param string $text Input text
     * @return string UTF-8 encoded text
     */
    public function ensureUtf8($text) {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8');
        }
        return $text;
    }
    
    /**
     * Fallback to imagestring if TTF font not available
     * Note: This has limited Thai support
     * 
     * @param resource $image GD image resource
     * @param string $text Text to render
     * @param int $x X coordinate
     * @param int $y Y coordinate
     * @param int $font_size Font size (for compatibility)
     * @param array $color RGB color array
     * @return bool Success status
     */
    private function drawFallbackText(&$image, $text, $x, $y, $font_size, $color) {
        $text_color = imagecolorallocate($image, $color[0], $color[1], $color[2]);
        
        // Use built-in font (limited support)
        imagestring($image, 5, $x, $y, $text, $text_color);
        
        return true;
    }
    
    /**
     * Get text dimensions for layout calculations
     * 
     * @param string $text Thai text
     * @param int $font_size Font size
     * @return array Array with width and height
     */
    public function getTextDimensions($text, $font_size = null) {
        $font_size = $font_size ?? $this->default_font_size;
        $text = $this->ensureUtf8($text);
        
        if (!file_exists($this->font_path)) {
            return [
                'width' => strlen($text) * ($font_size * 0.6),
                'height' => $font_size
            ];
        }
        
        $bbox = imagettfbbox($font_size, 0, $this->font_path, $text);
        
        return [
            'width' => $bbox[2] - $bbox[0],
            'height' => $bbox[1] - $bbox[7]
        ];
    }
    
    /**
     * Set custom font path
     * 
     * @param string $font_path Path to TTF font file
     * @return bool Success status
     */
    public function setFontPath($font_path) {
        if (file_exists($font_path)) {
            $this->font_path = $font_path;
            return true;
        }
        return false;
    }
    
    /**
     * Get available fonts
     * 
     * @return array List of available font files
     */
    public static function getAvailableFonts() {
        $fonts = [];
        $fonts_dir = 'assets/fonts/';
        
        if (is_dir($fonts_dir)) {
            $files = scandir($fonts_dir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'ttf') {
                    $fonts[pathinfo($file, PATHINFO_FILENAME)] = $fonts_dir . $file;
                }
            }
        }
        
        return $fonts;
    }
    
    /**
     * Create a test image with Thai text
     * For debugging purposes
     * 
     * @param string $text Thai text to test
     * @return string Path to created image
     */
    public function createTestImage($text) {
        $image = imagecreatetruecolor(800, 200);
        
        // White background
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        // Draw border
        $black = imagecolorallocate($image, 0, 0, 0);
        imagerectangle($image, 0, 0, 799, 199, $black);
        
        // Draw Thai text
        $this->drawThaiText($image, $text, 50, 80, 32, [26, 35, 126], 'left');
        
        // Save image
        $filename = 'test_thai_' . time() . '.png';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        imagepng($image, $filepath);
        imagedestroy($image);
        
        return $filepath;
    }
}
?>
