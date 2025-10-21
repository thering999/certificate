<?php
/**
 * Image Adapter Class - Provides unified image functions using GD or Imagick
 * This adapter provides compatibility for both GD Library and Imagick
 * Only declares functions that don't already exist (prevents redeclaration errors)
 * 
 * Usage: Include this file before using image functions:
 * require_once 'assets/image_adapter.class.php';
 */

// Determine which library is available
$GLOBALS['_IMAGE_LIBRARY'] = null;
if (extension_loaded('gd')) {
    $GLOBALS['_IMAGE_LIBRARY'] = 'gd';
} elseif (extension_loaded('imagick')) {
    $GLOBALS['_IMAGE_LIBRARY'] = 'imagick';
}

if ($GLOBALS['_IMAGE_LIBRARY'] === null) {
    die("Neither GD nor Imagick extension is loaded. Please install one of them.");
}

// ===== IF GD IS LOADED, NO WRAPPERS NEEDED =====
// GD functions already exist natively, so we return early
if ($GLOBALS['_IMAGE_LIBRARY'] === 'gd') {
    // All GD functions will work as-is
    // No need to declare wrappers - just return
    return;
}

// ===== IMAGICK WRAPPER FUNCTIONS (Only if GD is not available) =====
// These only declare if the function doesn't already exist

/**
 * Create image from JPEG file
 * @param string $filename Path to JPEG file
 * @return Imagick|false Imagick object on success, false on failure
 */
if (!function_exists('imagecreatefromjpeg')) {
    function imagecreatefromjpeg($filename) {
        try {
            if (!file_exists($filename)) {
                return false;
            }
            $imagick = new Imagick($filename);
            $imagick->setImageFormat('jpeg');
            return $imagick;
        } catch (Exception $e) {
            error_log("imagecreatefromjpeg error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Create image from PNG file
 * @param string $filename Path to PNG file
 * @return Imagick|false Imagick object on success, false on failure
 */
if (!function_exists('imagecreatefrompng')) {
    function imagecreatefrompng($filename) {
        try {
            if (!file_exists($filename)) {
                return false;
            }
            $imagick = new Imagick($filename);
            $imagick->setImageFormat('png');
            return $imagick;
        } catch (Exception $e) {
            error_log("imagecreatefrompng error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Create image from WebP file
 * @param string $filename Path to WebP file
 * @return Imagick|false Imagick object on success, false on failure
 */
if (!function_exists('imagecreatefromwebp')) {
    function imagecreatefromwebp($filename) {
        try {
            if (!file_exists($filename)) {
                return false;
            }
            $imagick = new Imagick($filename);
            $imagick->setImageFormat('webp');
            return $imagick;
        } catch (Exception $e) {
            error_log("imagecreatefromwebp error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Create a new image
 * @param int $width Image width
 * @param int $height Image height
 * @return Imagick New Imagick object
 */
if (!function_exists('imagecreatetruecolor')) {
    function imagecreatetruecolor($width, $height) {
        $imagick = new Imagick();
        $imagick->newImage($width, $height, new ImagickPixel('white'));
        $imagick->setImageFormat('png');
        return $imagick;
    }
}

/**
 * Allocate a color for an image
 * @param Imagick $image
 * @param int $red Red component (0-255)
 * @param int $green Green component (0-255)
 * @param int $blue Blue component (0-255)
 * @return ImagickPixel Color object
 */
if (!function_exists('imagecolorallocate')) {
    function imagecolorallocate($image, $red, $green, $blue) {
        $hexColor = sprintf('#%02x%02x%02x', $red, $green, $blue);
        return new ImagickPixel($hexColor);
    }
}

/**
 * Draw string on image
 * @param Imagick $image
 * @param int $font Font size (not used with Imagick, uses annotation)
 * @param int $x X coordinate
 * @param int $y Y coordinate
 * @param string $string Text to draw
 * @param ImagickPixel $color Text color
 * @return bool
 */
if (!function_exists('imagestring')) {
    function imagestring($image, $font, $x, $y, $string, $color) {
        try {
            if (!$image instanceof Imagick) {
                return false;
            }
            $draw = new ImagickDraw();
            $draw->setFillColor($color);
            $draw->setFont('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf');
            $draw->setFontSize($font * 3);
            $image->annotateImage($draw, $x, $y, 0, $string);
            return true;
        } catch (Exception $e) {
            error_log("imagestring error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Draw string with TrueType font
 * @param Imagick $image
 * @param int $size Font size
 * @param int $angle Text angle
 * @param int $x X coordinate
 * @param int $y Y coordinate
 * @param ImagickPixel $color Text color
 * @param string $fontfile Path to TTF font file
 * @param string $text Text to draw
 * @return array|bool Array with bounding box or false on error
 */
if (!function_exists('imagettftext')) {
    function imagettftext($image, $size, $angle, $x, $y, $color, $fontfile, $text) {
        try {
            if (!$image instanceof Imagick) {
                return false;
            }
            if (!file_exists($fontfile)) {
                error_log("Font file not found: $fontfile");
                return false;
            }
            $draw = new ImagickDraw();
            $draw->setFillColor($color);
            $draw->setFont($fontfile);
            $draw->setFontSize($size);
            if ($angle != 0) {
                $draw->rotate(-$angle);
            }
            $image->annotateImage($draw, $x, $y, $angle, $text);
            return array(
                0 => $x,
                1 => $y - $size,
                2 => $x + strlen($text) * $size * 0.5,
                3 => $y,
                4 => $x,
                5 => $y,
                6 => $x + strlen($text) * $size * 0.5,
                7 => $y - $size
            );
        } catch (Exception $e) {
            error_log("imagettftext error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Copy image
 * @param Imagick $dst_image Destination image
 * @param Imagick $src_image Source image
 * @param int $dst_x Destination X
 * @param int $dst_y Destination Y
 * @param int $src_x Source X (default 0)
 * @param int $src_y Source Y (default 0)
 * @param int $src_w Source width (default full)
 * @param int $src_h Source height (default full)
 * @return bool
 */
if (!function_exists('imagecopy')) {
    function imagecopy($dst_image, $src_image, $dst_x, $dst_y, $src_x = 0, $src_y = 0, $src_w = null, $src_h = null) {
        try {
            if (!$dst_image instanceof Imagick || !$src_image instanceof Imagick) {
                return false;
            }
            $clone = clone $src_image;
            if ($src_w === null) {
                $src_w = $clone->getImageWidth();
            }
            if ($src_h === null) {
                $src_h = $clone->getImageHeight();
            }
            if ($src_x != 0 || $src_y != 0 || $src_w != $clone->getImageWidth() || $src_h != $clone->getImageHeight()) {
                $clone->cropImage($src_w, $src_h, $src_x, $src_y);
            }
            if ($src_w != $clone->getImageWidth() || $src_h != $clone->getImageHeight()) {
                $clone->scaleImage($src_w, $src_h);
            }
            $dst_image->compositeImage($clone, Imagick::COMPOSITE_OVER, $dst_x, $dst_y);
            return true;
        } catch (Exception $e) {
            error_log("imagecopy error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Scale image
 * @param Imagick $image
 * @param int $new_width New width
 * @param int $new_height New height
 * @param int $mode Scaling mode (usually ignored)
 * @return bool
 */
if (!function_exists('imagescale')) {
    function imagescale($image, $new_width, $new_height = -1, $mode = IMG_BILINEAR) {
        try {
            if (!$image instanceof Imagick) {
                return false;
            }
            if ($new_height == -1) {
                $new_height = ($image->getImageHeight() / $image->getImageWidth()) * $new_width;
            }
            $image->scaleImage($new_width, $new_height);
            return true;
        } catch (Exception $e) {
            error_log("imagescale error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Output image to file or browser
 * @param Imagick $image
 * @param string $filename Optional filename to save to, or null for output
 * @param int $quality JPEG quality (0-100)
 * @return bool
 */
if (!function_exists('imagejpeg')) {
    function imagejpeg($image, $filename = null, $quality = 75) {
        try {
            if (!$image instanceof Imagick) {
                return false;
            }
            $image->setImageFormat('jpeg');
            $image->setImageCompression(Imagick::COMPRESSION_JPEG);
            $image->setImageCompressionQuality($quality);
            if ($filename === null) {
                echo $image->getImageBlob();
            } else {
                file_put_contents($filename, $image->getImageBlob());
            }
            return true;
        } catch (Exception $e) {
            error_log("imagejpeg error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Output image as PNG
 * @param Imagick $image
 * @param string $filename Optional filename to save to
 * @param int $quality Compression quality (0-9)
 * @return bool
 */
if (!function_exists('imagepng')) {
    function imagepng($image, $filename = null, $quality = 6) {
        try {
            if (!$image instanceof Imagick) {
                return false;
            }
            $image->setImageFormat('png');
            $image->setImageCompressionQuality($quality);
            if ($filename === null) {
                echo $image->getImageBlob();
            } else {
                file_put_contents($filename, $image->getImageBlob());
            }
            return true;
        } catch (Exception $e) {
            error_log("imagepng error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Output image as WebP
 * @param Imagick $image
 * @param string $filename Optional filename to save to
 * @param int $quality Quality (0-100)
 * @return bool
 */
if (!function_exists('imagewebp')) {
    function imagewebp($image, $filename = null, $quality = 80) {
        try {
            if (!$image instanceof Imagick) {
                return false;
            }
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality($quality);
            if ($filename === null) {
                echo $image->getImageBlob();
            } else {
                file_put_contents($filename, $image->getImageBlob());
            }
            return true;
        } catch (Exception $e) {
            error_log("imagewebp error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Destroy image resource
 * @param Imagick $image
 * @return bool
 */
if (!function_exists('imagedestroy')) {
    function imagedestroy($image) {
        try {
            if ($image instanceof Imagick) {
                $image->clear();
                $image->destroy();
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Get image width
 * @param Imagick $image
 * @return int
 */
if (!function_exists('imagesx')) {
    function imagesx($image) {
        try {
            if ($image instanceof Imagick) {
                return $image->getImageWidth();
            }
        } catch (Exception $e) {
        }
        return 0;
    }
}

/**
 * Get image height
 * @param Imagick $image
 * @return int
 */
if (!function_exists('imagesy')) {
    function imagesy($image) {
        try {
            if ($image instanceof Imagick) {
                return $image->getImageHeight();
            }
        } catch (Exception $e) {
        }
        return 0;
    }
}
