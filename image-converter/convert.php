#!/usr/bin/env php
<?php

/**
 * Image Converter CLI Tool
 * Converts images to AVIF/WebP format using ImageMagick
 * 
 * Usage:
 * php convert.php --input=/path/to/image.jpg --output=/path/to/output --format=avif
 * php convert.php --input=/path/to/folder --output=/path/to/output --format=webp --recursive
 */

class ImageConverter {
    private $supportedInputFormats = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp'];
    private $supportedOutputFormats = ['avif', 'webp', 'both'];
    private $quality = 80;
    
    public function __construct() {
        if (!extension_loaded('imagick')) {
            $this->error("ImageMagick extension is not installed!");
        }
    }
    
    /**
     * Main conversion method
     */
    public function convert($inputPath, $outputPath, $format, $recursive = false, $quality = 80) {
        $this->quality = $quality;
        
        if (!in_array(strtolower($format), $this->supportedOutputFormats)) {
            $this->error("Unsupported output format: $format. Supported: " . implode(', ', $this->supportedOutputFormats));
        }
        
        if (!file_exists($inputPath)) {
            $this->error("Input path does not exist: $inputPath");
        }
        
        // Create output directory if it doesn't exist
        if (!is_dir($outputPath)) {
            if (!mkdir($outputPath, 0755, true)) {
                $this->error("Failed to create output directory: $outputPath");
            }
        }
        
        // Handle "both" format by converting to array
        $formats = $format === 'both' ? ['avif', 'webp'] : [$format];
        
        if (is_file($inputPath)) {
            $this->convertSingleFile($inputPath, $outputPath, $formats);
        } elseif (is_dir($inputPath)) {
            $this->convertDirectory($inputPath, $outputPath, $formats, $recursive);
        } else {
            $this->error("Invalid input path: $inputPath");
        }
    }
    
    /**
     * Convert a single file
     */
    private function convertSingleFile($inputFile, $outputDir, $formats) {
        $inputExtension = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));
        
        if (!in_array($inputExtension, $this->supportedInputFormats)) {
            $this->log("Skipping unsupported file: $inputFile");
            return false;
        }
        
        $filename = pathinfo($inputFile, PATHINFO_FILENAME);
        $originalExtension = pathinfo($inputFile, PATHINFO_EXTENSION);
        $baseFilename = $filename . '.' . $originalExtension;
        
        $success = true;
        $results = [];
        
        foreach ($formats as $format) {
            $outputFile = rtrim($outputDir, '/') . '/' . $baseFilename . '.' . $format;
            
            try {
                $imagick = new Imagick($inputFile);
                
                // Set format
                $imagick->setImageFormat($format);
                
                // Set quality based on format
                if ($format === 'webp') {
                    $imagick->setImageCompressionQuality($this->quality);
                    $imagick->setOption('webp:lossless', 'false');
                } elseif ($format === 'avif') {
                    $imagick->setImageCompressionQuality($this->quality);
                    $imagick->setOption('heic:speed', '4'); // Balance between speed and compression
                }
                
                // Strip metadata to reduce file size
                $imagick->stripImage();
                
                // Write the image
                $imagick->writeImage($outputFile);
                $imagick->clear();
                $imagick->destroy();
                
                $inputSize = filesize($inputFile);
                $outputSize = filesize($outputFile);
                $compression = round((1 - $outputSize / $inputSize) * 100, 1);
                
                $results[] = [
                    'format' => strtoupper($format),
                    'file' => basename($outputFile),
                    'size' => $this->formatBytes($outputSize),
                    'compression' => $compression
                ];
                
            } catch (Exception $e) {
                $this->log("✗ Failed to convert $inputFile to $format: " . $e->getMessage());
                $success = false;
            }
        }
        
        if (!empty($results)) {
            $inputSize = filesize($inputFile);
            $inputSizeFormatted = $this->formatBytes($inputSize);
            
            if (count($results) === 1) {
                $result = $results[0];
                $this->log("✓ Converted: " . basename($inputFile) . " -> " . $result['file'] . 
                          " (Size: $inputSizeFormatted -> " . $result['size'] . 
                          ", Compression: " . $result['compression'] . "%)");
            } else {
                $this->log("✓ Converted: " . basename($inputFile) . " (Size: $inputSizeFormatted)");
                foreach ($results as $result) {
                    $this->log("  -> " . $result['format'] . ": " . $result['file'] . 
                              " (" . $result['size'] . ", " . $result['compression'] . "% compression)");
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Convert all images in a directory
     */
    private function convertDirectory($inputDir, $outputDir, $formats, $recursive) {
        $iterator = $recursive ? 
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($inputDir)) :
            new DirectoryIterator($inputDir);
            
        $converted = 0;
        $total = 0;
        
        foreach ($iterator as $file) {
            // Skip dot files and directories
            if ($file->isDir() || $file->getFilename() === '.' || $file->getFilename() === '..') {
                continue;
            }
            
            $inputFile = $file->getPathname();
            $inputExtension = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));
            
            if (in_array($inputExtension, $this->supportedInputFormats)) {
                $total++;
                
                // Maintain directory structure in output
                if ($recursive && $iterator instanceof RecursiveIteratorIterator) {
                    $relativePath = substr(dirname($inputFile), strlen($inputDir));
                    $outputSubDir = $outputDir . $relativePath;
                    if (!is_dir($outputSubDir)) {
                        mkdir($outputSubDir, 0755, true);
                    }
                    $targetOutputDir = $outputSubDir;
                } else {
                    $targetOutputDir = $outputDir;
                }
                
                if ($this->convertSingleFile($inputFile, $targetOutputDir, $formats)) {
                    $converted++;
                }
            }
        }
        
        $formatList = is_array($formats) ? implode(' + ', array_map('strtoupper', $formats)) : strtoupper($formats);
        $this->log("\nConversion complete: $converted/$total files converted successfully to $formatList.");
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Log message to console
     */
    private function log($message) {
        echo $message . PHP_EOL;
    }
    
    /**
     * Display error and exit
     */
    private function error($message) {
        echo "Error: $message" . PHP_EOL;
        exit(1);
    }
    
    /**
     * Display usage information
     */
    public function showUsage() {
        echo "Image Converter CLI Tool\n";
        echo "========================\n\n";
        echo "Usage:\n";
        echo "  php convert.php [OPTIONS]\n\n";
        echo "Options:\n";
        echo "  --input=PATH      Input file or directory path (required)\n";
        echo "  --output=PATH     Output directory path (required)\n";
        echo "  --format=FORMAT   Output format: avif, webp, or both (required)\n";
        echo "  --recursive       Convert subdirectories recursively (optional)\n";
        echo "  --quality=NUM     Image quality 1-100 (default: 80)\n";
        echo "  --help            Show this help message\n\n";
        echo "Examples:\n";
        echo "  # Convert single file to AVIF\n";
        echo "  php convert.php --input=/path/to/image.jpg --output=/path/to/output --format=avif\n\n";
        echo "  # Convert single file to both AVIF and WebP\n";
        echo "  php convert.php --input=/path/to/image.jpg --output=/path/to/output --format=both\n\n";
        echo "  # Convert entire directory to WebP\n";
        echo "  php convert.php --input=/path/to/images --output=/path/to/output --format=webp\n\n";
        echo "  # Convert directory recursively to both formats with custom quality\n";
        echo "  php convert.php --input=/path/to/images --output=/path/to/output --format=both --recursive --quality=90\n\n";
        echo "Supported input formats: " . implode(', ', $this->supportedInputFormats) . "\n";
        echo "Supported output formats: " . implode(', ', $this->supportedOutputFormats) . "\n";
        echo "\nNote: Using 'both' format creates two files: image.jpg.avif and image.jpg.webp\n";
    }
}

// Parse command line arguments
function parseArguments($argv) {
    $args = [];
    
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            $key = $parts[0];
            $value = isset($parts[1]) ? $parts[1] : true;
            $args[$key] = $value;
        }
    }
    
    return $args;
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line!");
}

$converter = new ImageConverter();
$args = parseArguments($argv);

// Show help
if (isset($args['help']) || empty($args)) {
    $converter->showUsage();
    exit(0);
}

// Validate required arguments
$required = ['input', 'output', 'format'];
foreach ($required as $arg) {
    if (!isset($args[$arg])) {
        echo "Error: Missing required argument --$arg\n\n";
        $converter->showUsage();
        exit(1);
    }
}

// Extract arguments
$inputPath = $args['input'];
$outputPath = $args['output'];
$format = strtolower($args['format']);
$recursive = isset($args['recursive']);
$quality = isset($args['quality']) ? (int)$args['quality'] : 80;

// Validate quality
if ($quality < 1 || $quality > 100) {
    echo "Error: Quality must be between 1 and 100\n";
    exit(1);
}

// Start conversion
echo "Starting image conversion...\n";
echo "Input: $inputPath\n";
echo "Output: $outputPath\n";
echo "Format: " . ($format === 'both' ? 'AVIF + WebP' : strtoupper($format)) . "\n";
echo "Quality: $quality\n";
echo "Recursive: " . ($recursive ? 'Yes' : 'No') . "\n";
echo str_repeat('-', 50) . "\n";

$converter->convert($inputPath, $outputPath, $format, $recursive, $quality);

echo "\nDone!\n";
?>
