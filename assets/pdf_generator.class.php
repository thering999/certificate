<?php
/**
 * PDF Generator Class using TCPDF
 * Converts PNG images to PDF format with Thai language support
 * 
 * @author HDC AI System
 * @version 1.0.0
 */

// Check if TCPDF is available
if (!class_exists('TCPDF')) {
    // Define a simple TCPDF wrapper that converts images to PDF
    class SimplePdfGenerator {
        private $images = [];
        
        public function __construct() {}
        
        /**
         * Add an image to the PDF
         */
        public function addImage($imagePath, $name = '') {
            if (file_exists($imagePath)) {
                $this->images[] = [
                    'path' => $imagePath,
                    'name' => $name
                ];
            }
        }
        
        /**
         * Generate PDF from images using Imagick
         */
        public function generatePdf($outputPath) {
            if (empty($this->images)) {
                return false;
            }
            
            // Check if Imagick is available for PDF conversion
            if (!extension_loaded('imagick')) {
                return false;
            }
            
            try {
                $imagick = new Imagick();
                $imagick->setImageFormat('pdf');
                
                // Add each image to the PDF
                foreach ($this->images as $img) {
                    $imgMagick = new Imagick($img['path']);
                    $imgMagick->setImageFormat('png');
                    $imagick->addImage($imgMagick);
                }
                
                // Write PDF to file
                if ($imagick->writeImages($outputPath, true)) {
                    return true;
                }
            } catch (Exception $e) {
                error_log('PDF Generation Error: ' . $e->getMessage());
                return false;
            }
            
            return false;
        }
        
        /**
         * Get number of images
         */
        public function getImageCount() {
            return count($this->images);
        }
    }
}

/**
 * PDF Export class for certificate system
 */
class CertificatePdfExporter {
    
    /**
     * Convert PNG image to PDF
     * 
     * @param string $pngPath Path to PNG file
     * @param string $pdfPath Output PDF path
     * @return bool Success status
     */
    public static function convertPngToPdf($pngPath, $pdfPath) {
        if (!file_exists($pngPath)) {
            return false;
        }
        
        // Try using Imagick
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick($pngPath);
                $imagick->setImageFormat('pdf');
                return $imagick->writeImage($pdfPath);
            } catch (Exception $e) {
                error_log('Imagick PDF conversion failed: ' . $e->getMessage());
            }
        }
        
        // If Imagick fails or not available, try GhostScript command
        if (self::isCommandAvailable('convert')) {
            $command = escapeshellcmd("convert \"$pngPath\" \"$pdfPath\"");
            $output = null;
            $return_var = null;
            exec($command, $output, $return_var);
            
            if ($return_var === 0 && file_exists($pdfPath)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a command is available on the system
     */
    private static function isCommandAvailable($command) {
        $output = null;
        $return_var = null;
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            exec("where $command", $output, $return_var);
        } else {
            // Unix/Linux
            exec("which $command", $output, $return_var);
        }
        
        return $return_var === 0;
    }
    
    /**
     * Create multi-page PDF from multiple PNG images
     * 
     * @param array $pngPaths Array of PNG file paths
     * @param string $pdfPath Output PDF path
     * @return bool Success status
     */
    public static function createMultiPagePdf($pngPaths, $pdfPath) {
        if (empty($pngPaths)) {
            return false;
        }
        
        // If single image, use simple conversion
        if (count($pngPaths) === 1) {
            return self::convertPngToPdf($pngPaths[0], $pdfPath);
        }
        
        // Try using Imagick for multi-page PDF
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick();
                $imagick->setImageFormat('pdf');
                
                foreach ($pngPaths as $pngPath) {
                    if (file_exists($pngPath)) {
                        $img = new Imagick($pngPath);
                        $img->setImageFormat('png');
                        $imagick->addImage($img);
                    }
                }
                
                return $imagick->writeImages($pdfPath, true);
            } catch (Exception $e) {
                error_log('Imagick multi-page PDF failed: ' . $e->getMessage());
            }
        }
        
        // Fallback: create multiple PDFs and merge them
        $tempPdfs = [];
        foreach ($pngPaths as $i => $pngPath) {
            $tempPdf = sys_get_temp_dir() . '/cert_' . $i . '_' . time() . '.pdf';
            if (self::convertPngToPdf($pngPath, $tempPdf)) {
                $tempPdfs[] = $tempPdf;
            }
        }
        
        if (count($tempPdfs) > 0) {
            // Try to merge PDFs using ghostscript
            if (self::mergePdfFiles($tempPdfs, $pdfPath)) {
                // Clean up temp PDFs
                foreach ($tempPdfs as $tmpPdf) {
                    @unlink($tmpPdf);
                }
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Merge multiple PDF files
     */
    private static function mergePdfFiles($pdfFiles, $outputPath) {
        if (self::isCommandAvailable('gs')) {
            // GhostScript available
            $files = implode(' ', array_map('escapeshellarg', $pdfFiles));
            $command = "gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=\"" . escapeshellarg($outputPath) . "\" -dBATCH $files";
            
            $output = null;
            $return_var = null;
            exec($command, $output, $return_var);
            
            return $return_var === 0 && file_exists($outputPath);
        }
        
        return false;
    }
}
?>
