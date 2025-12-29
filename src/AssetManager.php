<?php

namespace KnifeLemon\CommentTemplate;

/**
 * AssetManager handles copying and managing static assets
 */
class AssetManager
{
    private string $sourceDir;
    private string $publicDir;
    private string $webRootPath;
    private array $processedFiles = [];

    public function __construct(string $sourceDir, string $publicDir, string $webRootPath = '')
    {
        $this->sourceDir = rtrim($sourceDir, '/\\');
        $this->publicDir = rtrim($publicDir, '/\\');
        $this->webRootPath = $webRootPath ?: basename($publicDir);
    }

    /**
     * Copy asset file to public directory if needed
     *
     * @param string $relativePath Relative path from source directory
     * @return string Public URL path
     */
    public function copyAsset(string $relativePath): string
    {
        $relativeOriginal = ltrim($relativePath, '/\\');

        // Avoid duplicating the asset root when callers pass paths that already include it
        $webRootNormalized = trim(str_replace(['\\', '/'], '/', $this->webRootPath), '/');
        $relativeNormalized = str_replace(['\\', '/'], '/', $relativeOriginal);
        $publicRelativeNormalized = $relativeNormalized;
        
        if ($webRootNormalized !== '' && (
            $relativeNormalized === $webRootNormalized ||
            str_starts_with($relativeNormalized, $webRootNormalized . '/'))
        ) {
            $publicRelativeNormalized = ltrim(substr($relativeNormalized, strlen($webRootNormalized)), '/');
        }

        // Source uses the original relative path; public path drops the duplicated root
        $sourcePath = $this->sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeOriginal);
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $publicRelativeNormalized);
        $publicPath = $this->publicDir . DIRECTORY_SEPARATOR . $relativePath;
        
        // Check if it's a directory
        if (is_dir($sourcePath)) {
            $this->copyDirectory($sourcePath, $publicPath);
            // Return directory URL - use webRootPath
            return '/' . $this->webRootPath . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
        }
        
        // Create directory if it doesn't exist
        $publicDirPath = dirname($publicPath);
        if (!is_dir($publicDirPath)) {
            mkdir($publicDirPath, 0755, true);
        }
        
        // Copy file if source is newer or target doesn't exist
        if (file_exists($sourcePath)) {
            if (!file_exists($publicPath) || filemtime($sourcePath) > filemtime($publicPath)) {
                copy($sourcePath, $publicPath);
                $this->processedFiles[] = $relativePath;
            }
        }
        
        // Return public URL path - use webRootPath
        return '/' . $this->webRootPath . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    }

    /**
     * Process base64 directive and copy image to public folder
     *
     * @param string $relativePath Relative path from source directory
     * @return string Base64 data URI
     */
    public function processBase64(string $relativePath): string
    {
        $sourcePath = $this->sourceDir . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        
        if (file_exists($sourcePath)) {
            // Also copy to public for browser caching option
            $this->copyAsset($relativePath);
            
            // Return base64 data URI
            $data = file_get_contents($sourcePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $sourcePath);
            finfo_close($finfo);
            
            return 'data:' . $mimeType . ';base64,' . base64_encode($data);
        }
        
        return '';
    }

    /**
     * Copy entire directory recursively
     *
     * @param string $sourceDir Source directory path
     * @param string $targetDir Target directory path
     */
    private function copyDirectory(string $sourceDir, string $targetDir): void
    {
        if (!is_dir($sourceDir)) {
            return;
        }

        // Create target directory if it doesn't exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = $iterator->getSubPathName();
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $relativePath;

            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $sourceFilePath = $file->getPathname();
                
                // Copy file if source is newer or target doesn't exist
                if (!file_exists($targetPath) || filemtime($sourceFilePath) > filemtime($targetPath)) {
                    copy($sourceFilePath, $targetPath);
                    $this->processedFiles[] = str_replace($this->sourceDir . DIRECTORY_SEPARATOR, '', $sourceFilePath);
                }
            }
        }
    }

    /**
     * Get list of processed files
     *
     * @return array
     */
    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }

    /**
     * Clean old asset files (optional maintenance function)
     *
     * @param int $maxAge Maximum age in seconds
     */
    public function cleanOldAssets(int $maxAge = 86400): void
    {
        $this->cleanDirectory($this->publicDir, $maxAge);
    }

    private function cleanDirectory(string $dir, int $maxAge): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        $now = time();
        foreach ($iterator as $file) {
            if ($file->isFile() && ($now - $file->getMTime()) > $maxAge) {
                unlink($file->getPathname());
            }
        }
    }
}