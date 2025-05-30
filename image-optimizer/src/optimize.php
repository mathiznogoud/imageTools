<?php

require 'vendor/autoload.php';

use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Svgo;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Avifenc;

class AdvancedImageOptimizer
{
    private OptimizerChain $optimizerChain;
    private array $stats = [
        'total' => 0,
        'processed' => 0,
        'errors' => 0,
        'saved_bytes' => 0,
    ];
    private bool $backupEnabled;
    private int $quality;

    public function __construct()
    {
        $this->backupEnabled = filter_var(getenv('BACKUP_ENABLED'), FILTER_VALIDATE_BOOLEAN) ?? true;
        $this->quality = (int)(getenv('OPTIMIZATION_QUALITY') ?? 85);
        
        $this->optimizerChain = new OptimizerChain();
        $this->setupOptimizers();
        
        // Create backup directory if enabled
        if ($this->backupEnabled && !is_dir('/images/backup')) {
            mkdir('/images/backup', 0755, true);
        }
    }

    private function setupOptimizers(): void
    {
        $this->optimizerChain->addOptimizer(new Jpegoptim([
            '--strip-all',
            '--all-progressive',
            '-m' . $this->quality,
            '--quiet'
        ]));

        $this->optimizerChain->addOptimizer(new Pngquant([
            '--force',
            '--quality=' . ($this->quality-20) . '-' . $this->quality,
            '--strip',
            '--quiet'
        ]));

        $this->optimizerChain->addOptimizer(new Optipng([
            '-i0',
            '-o2',
            '-quiet'
        ]));

        $this->optimizerChain->addOptimizer(new Svgo([
            '--multipass',
            '--pretty',
            '--plugin removeDimensions'
        ]));

        $this->optimizerChain->addOptimizer(new Gifsicle([
            '-O3',
            '--careful',
            '--quiet'
        ]));

        $this->optimizerChain->addOptimizer(new Cwebp([
            '-m 6',
            '-q ' . $this->quality,
            '-mt',
            '-quiet'
        ]));

        $this->optimizerChain->addOptimizer(new Avifenc([
            '-s 0',
            '--min 0',
            '--max 63'
        ]));
    }

    private function createBackup(string $filepath): void
    {
        if (!$this->backupEnabled) return;

        $relativePath = str_replace('/images/', '', $filepath);
        $backupPath = '/images/backup/' . $relativePath;
        $backupDir = dirname($backupPath);

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        copy($filepath, $backupPath);
    }

    public function optimizeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            echo "Error: Directory $directory not found\n";
            return;
        }

        $files = $this->getAllImages($directory);
        $this->stats['total'] = count($files);

        if ($this->stats['total'] === 0) {
            echo "No images found in $directory\n";
            return;
        }

        echo "Starting optimization with quality: {$this->quality}%\n";
        echo "Backup " . ($this->backupEnabled ? "enabled" : "disabled") . "\n\n";

        foreach ($files as $file) {
            $this->optimizeFile($file);
        }

        $this->printSummary();
    }

    private function getAllImages(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $this->isImage($file)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function isImage($file): bool
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg']);
    }

    private function optimizeFile(string $filepath): void
    {
        try {
            // Skip backup directory
            if (strpos($filepath, '/images/backup/') !== false) {
                return;
            }

            $originalSize = filesize($filepath);
            echo "Processing: " . str_replace('/images/', '', $filepath) . "\n";
            echo "Original size: " . $this->formatBytes($originalSize) . "\n";

            // Create backup before optimization
            $this->createBackup($filepath);

            $this->optimizerChain->optimize($filepath);
            
            $newSize = filesize($filepath);
            $savedBytes = $originalSize - $newSize;
            $this->stats['saved_bytes'] += $savedBytes;
            
            echo "New size: " . $this->formatBytes($newSize) . "\n";
            echo "Saved: " . $this->formatBytes($savedBytes) . " (" . 
                 round(($savedBytes / $originalSize) * 100, 2) . "%)\n";
            echo str_repeat("-", 50) . "\n";
            
            $this->stats['processed']++;
        } catch (Exception $e) {
            echo "Error processing $filepath: " . $e->getMessage() . "\n";
            $this->stats['errors']++;
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function printSummary(): void
    {
        echo "\nOptimization Summary:\n";
        echo "Total files processed: " . $this->stats['processed'] . "/" . $this->stats['total'] . "\n";
        echo "Errors encountered: " . $this->stats['errors'] . "\n";
        echo "Total space saved: " . $this->formatBytes($this->stats['saved_bytes']) . "\n";
        
        if ($this->stats['processed'] > 0) {
            $averageSaved = $this->stats['saved_bytes'] / $this->stats['processed'];
            echo "Average savings per file: " . $this->formatBytes($averageSaved) . "\n";
        }

        if ($this->backupEnabled) {
            echo "Backups created in: /images/backup/\n";
        }
    }
}

// Run the optimizer
$optimizer = new AdvancedImageOptimizer();
$optimizer->optimizeDirectory('/images');
