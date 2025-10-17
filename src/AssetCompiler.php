<?php

namespace KnifeLemon\CommentTemplate;

use MatthiasMullie\Minify;

/**
 * AssetCompiler handles the compilation and minification of CSS and JavaScript assets.
 */
class AssetCompiler
{
    private string $publicPath;
    private string $skinPath;
    private int $layoutModifyTime;
    private string $assetPath;

    public function __construct(string $publicPath, string $skinPath, int $layoutModifyTime = 0, string $assetPath = 'assets')
    {
        $this->publicPath = $publicPath;
        $this->skinPath = $skinPath;
        $this->layoutModifyTime = $layoutModifyTime;
        
        // Support both relative and absolute asset paths
        if (strpos($assetPath, DIRECTORY_SEPARATOR) !== false || strpos($assetPath, '/') !== false) {
            // Absolute path - extract relative part from publicPath
            $this->assetPath = str_replace($publicPath, '', $assetPath);
            $this->assetPath = trim($this->assetPath, '/\\');
        } else {
            // Relative path
            $this->assetPath = trim($assetPath, '/\\');
        }
    }

    /**
     * Compile assets and inject them into HTML
     *
     * @param string $assetType Asset type constant
     * @param string $templatePath Template file path
     * @param string $html HTML content to modify
     * @param array $patterns Regex patterns for asset detection
     * @return void
     */
    public function compileAssets(string $assetType, string $templatePath, string &$html, array $patterns): void
    {
        $assetExtension = AssetType::getExtension($assetType);
        $pathInfo = $this->getDirAndFileName($templatePath);

        $appendFileName = $this->getAppendFileName($assetType);
        
        // Hashed Directory
        $hashedDir = $this->hashMurmur3($pathInfo['dir']);

        // Create Cache Compile Path
        $cachePath = $this->createParentPaths($this->assetPath . DIRECTORY_SEPARATOR . $assetExtension . DIRECTORY_SEPARATOR . $hashedDir);

        if (!AssetType::isSingleType($assetType)) {
            $this->compileMultipleAssets($assetType, $assetExtension, $pathInfo, $cachePath, $appendFileName, $templatePath, $html, $patterns);
        } else {
            $this->compileSingleAssets($assetType, $assetExtension, $cachePath, $appendFileName, $html, $patterns);
        }
    }

    /**
     * Compile multiple assets into a single minified file
     */
    private function compileMultipleAssets(string $assetType, string $assetExtension, array $pathInfo, string $cachePath, string $appendFileName, string $templatePath, string &$html, array $patterns): void
    {
        // create one fileName for all files
        $hashedFileName = $this->hashMurmur3($appendFileName . $pathInfo['name']);

        $minifier = null;
        if ($assetType === AssetType::CSS) {
            $minifier = new Minify\CSS();
        } else {
            $minifier = new Minify\JS();
        }

        $files = $this->getAssetFiles($html, $patterns[$assetType]);
        
        // Only process if there are files to compile
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            // Read file content and process asset directives
            $fileContent = file_get_contents($file);
            $processedContent = $this->processAssetDirectives($fileContent);
            
            // Create a temporary file with processed content
            $tempFile = tempnam(sys_get_temp_dir(), 'ct_') . '.' . $assetExtension;
            file_put_contents($tempFile, $processedContent);
            
            // Add the temporary file to minifier
            $minifier->add($tempFile);
            
            // Register for cleanup
            register_shutdown_function(function() use ($tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            });
        }

        // Minify 처리와 파일 저장 처리부
        $minifiedPath = $cachePath . DIRECTORY_SEPARATOR . $hashedFileName . '.' . $assetExtension;
        $minifiedUrl = str_replace($this->publicPath, '', $minifiedPath);
        $minifiedUrl = str_replace(DIRECTORY_SEPARATOR, '/', $minifiedUrl);

        $minifiedContent = $minifier->minify();

        $this->saveFileIfNecessary($minifiedPath, $minifiedContent, $files, $templatePath);

        $this->injectAssetHtml($assetType, $minifiedUrl, $html);
    }

    /**
     * Compile single assets (no minification)
     */
    private function compileSingleAssets(string $assetType, string $assetExtension, string $cachePath, string $appendFileName, string &$html, array $patterns): void
    {
        $files = $this->getAssetFiles($html, $patterns[$assetType]);
        
        // Only process if there are files to compile
        if (empty($files)) {
            return;
        }
        
        foreach ($files as $file) {
            $singleAsset = file_get_contents($file);
            
            // Process asset directives in the file content
            $processedAsset = $this->processAssetDirectives($singleAsset);

            // get file safefilename
            $hashedFileName = $this->hashMurmur3($appendFileName . $this->getDirAndFileName($file)['name']);

            // Save
            $singleAssetPath = $cachePath . DIRECTORY_SEPARATOR . $hashedFileName . '.' . $assetExtension;
            $singleAssetUrl = str_replace($this->publicPath, '', $singleAssetPath);
            $singleAssetUrl = str_replace(DIRECTORY_SEPARATOR, '/', $singleAssetUrl);

            // if file exists And modify time is older than source file
            if (!file_exists($singleAssetPath) || filemtime($singleAssetPath) < filemtime($file)) {
                file_put_contents($singleAssetPath, $processedAsset);
            }
            
            $this->injectAssetHtml($assetType, $singleAssetUrl, $html);
        }
    }

    /**
     * Process asset directives in CSS/JS files
     *
     * @param string $content File content
     * @return string Processed content with asset URLs
     */
    private function processAssetDirectives(string $content): string
    {
        // Process @asset directive
        if (preg_match_all('/<!--@asset\((.*?)\)-->/', $content, $matches, PREG_SET_ORDER)) {
            // Use the same logic as CommentTemplate for asset path calculation
            $assetTargetPath = (strpos($this->assetPath, DIRECTORY_SEPARATOR) !== false || strpos($this->assetPath, '/') !== false) 
                ? $this->assetPath 
                : $this->publicPath . DIRECTORY_SEPARATOR . $this->assetPath;
            
            // Calculate webRootPath for URL generation
            if (strpos($this->assetPath, DIRECTORY_SEPARATOR) !== false || strpos($this->assetPath, '/') !== false) {
                // Absolute path - calculate relative path from publicPath
                $webRootPath = str_replace($this->publicPath, '', $this->assetPath);
                $webRootPath = trim($webRootPath, '/\\');
            } else {
                // Relative path
                $webRootPath = $this->assetPath;
            }
            
            $assetManager = new AssetManager($this->skinPath, $assetTargetPath, $webRootPath);
            
            foreach ($matches as $match) {
                $fullMatch = $match[0]; // <!--@asset(path)-->
                $path = $match[1]; // path
                $publicUrl = $assetManager->copyAsset($path);
                $content = str_replace($fullMatch, $publicUrl, $content);
            }
        }

        // Process @base64 directive  
        if (preg_match_all('/<!--@base64\((.*?)\)-->/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullMatch = $match[0]; // <!--@base64(path)-->
                $path = $match[1]; // path
                $realPath = $this->skinPath . DIRECTORY_SEPARATOR . $path;
                if (file_exists($realPath)) {
                    $data = file_get_contents($realPath);
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $realPath);
                    finfo_close($finfo);
                    $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($data);
                    $content = str_replace($fullMatch, $base64, $content);
                }
            }
        }

        return $content;
    }

    /**
     * Get append filename based on asset type
     */
    private function getAppendFileName(string $assetType): string
    {
        switch ($assetType) {
            case AssetType::JS_TOP:
                return 'top_';
            case AssetType::JS_TOP_ASYNC:
                return 'top_async_';
            case AssetType::JS_TOP_DEFER:
                return 'top_defer_';
            case AssetType::JS_SINGLE:
                return 'single_';
            case AssetType::JS_SINGLE_ASYNC:
                return 'single_async_';
            case AssetType::JS_SINGLE_DEFER:
                return 'single_defer_';
            case AssetType::JS:
                return 'js_';
            case AssetType::JS_ASYNC:
                return 'js_async_';
            case AssetType::JS_DEFER:
                return 'js_defer_';
            case AssetType::CSS:
                return 'css_';
            case AssetType::CSS_SINGLE:
                return 'single_css_';
            default:
                return '';
        }
    }

    /**
     * Save file if necessary based on modification times
     */
    private function saveFileIfNecessary(string $minifiedPath, string $minifiedContent, array $files, string $templatePath): void
    {
        $minifiedPathExists = file_exists($minifiedPath);
        $minifiedPathMTime = $minifiedPathExists ? filemtime($minifiedPath) : PHP_INT_MIN;
        $maxFileMTime = count($files) > 0 ? max(array_map('filemtime', $files)) : PHP_INT_MIN;
        $templatePathMTime = file_exists($templatePath) ? filemtime($templatePath) : PHP_INT_MIN;

        $fileSizeMatches = $minifiedPathExists ? strlen($minifiedContent) === filesize($minifiedPath) : false;

        if (!$minifiedPathExists || 
            ($minifiedPathMTime < $this->layoutModifyTime ||
            $minifiedPathMTime < $maxFileMTime || 
            $minifiedPathMTime < $templatePathMTime ||
            !$fileSizeMatches)) {
            file_put_contents($minifiedPath, $minifiedContent);
        }
    }

    /**
     * Inject asset HTML into the document
     */
    private function injectAssetHtml(string $assetType, string $assetUrl, string &$html): void
    {
        switch ($assetType) {
            case AssetType::CSS:
            case AssetType::CSS_SINGLE:
                $assetHtml = '<link rel="stylesheet" href="' . $assetUrl . '">';
                // inject before </head>
                if (strpos($html, "</head>") !== false) {
                    $html = str_replace("</head>", $assetHtml . "\n</head>", $html);
                } else {
                    $html = $assetHtml . $html;
                }
                break;
            case AssetType::JS:
            case AssetType::JS_ASYNC:
            case AssetType::JS_DEFER:
            case AssetType::JS_SINGLE:
            case AssetType::JS_SINGLE_ASYNC:
            case AssetType::JS_SINGLE_DEFER:
                $loadType = '';
                if (in_array($assetType, [AssetType::JS_ASYNC, AssetType::JS_SINGLE_ASYNC])) {
                    $loadType = ' async';
                } else if (in_array($assetType, [AssetType::JS_DEFER, AssetType::JS_SINGLE_DEFER])) {
                    $loadType = ' defer';
                }
                $assetHtml = '<script src="' . $assetUrl . '"' . $loadType . '></script>';
                // inject before </body>
                if (strpos($html, "</body>") !== false) {
                    $html = str_replace("</body>", $assetHtml . "\n</body>", $html);
                } else {
                    $html = $html . $assetHtml;
                }
                break;
            case AssetType::JS_TOP:
            case AssetType::JS_TOP_ASYNC:
            case AssetType::JS_TOP_DEFER:
                $loadType = '';
                if ($assetType === AssetType::JS_TOP_ASYNC) {
                    $loadType = ' async';
                } else if ($assetType === AssetType::JS_TOP_DEFER) {
                    $loadType = ' defer';
                }
                $assetHtml = '<script src="' . $assetUrl . '"' . $loadType . '></script>';
                // inject before </head>
                if (strpos($html, "</head>") !== false) {
                    $html = str_replace("</head>", $assetHtml . "\n</head>", $html);
                } else {
                    $html = $assetHtml . $html;
                }
                break;
            default:
                break;
        }
    }

    /**
     * Extract asset files from HTML and return file paths
     */
    private function getAssetFiles(string &$html, string $pattern): array
    {
        $files = [];
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $index => $path) {
                // replace comment to empty
                $html = str_replace($matches[0][$index], '', $html);

                $realPath = $this->skinPath . DIRECTORY_SEPARATOR . $path;
                if (file_exists($realPath)) {
                    $files[] = $realPath;
                }
            }
        }
        return $files;
    }

    /**
     * Create parent directories recursively
     */
    private function createParentPaths(string $path): string
    {
        $paths = explode(DIRECTORY_SEPARATOR, $path);
        $realPath = $this->publicPath;
        foreach ($paths as $path) {
            $realPath .= DIRECTORY_SEPARATOR . $path;
            if (!file_exists($realPath)) {
                mkdir($realPath);
            }
        }
        return $realPath;
    }

    /**
     * Get directory and filename from path
     */
    private function getDirAndFileName(string $path): array
    {
        // explode with DIRECTORY_SEPARATOR OR /
        $paths = preg_split('/[\/\\\\]/', $path);
        $fileName = array_pop($paths);
        $dir = end($paths);
        return [
            'dir' => $dir,
            'name' => $fileName
        ];
    }

    /**
     * Generate MurmurHash3 for string
     * @see https://stackoverflow.com/a/74506795/5317837
     */
    private function hashMurmur3(string $string): string
    {
        $string = array_values(unpack('C*', $string));
        $klen = count($string);
        $h1 = 0;
        $remainder = 0;
        $i = 0;
    
        for ($bytes = $klen - ($remainder = $klen & 3); $i < $bytes;) {
            $k1 = $string[$i] | ($string[++$i] << 8) | ($string[++$i] << 16) | ($string[++$i] << 24);
            ++$i;
            $k1 = (((($k1 & 0xffff) * 0xcc9e2d51) + (((((($k1 >= 0) ? ($k1 >> 16) : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0xcc9e2d51) & 0xffff) << 16))) & 0xffffffff;
            $k1 = $k1 << 15 | (($k1 >= 0) ? ($k1 >> 17) : (($k1 & 0x7fffffff) >> 17) | 0x4000);
            $k1 = (((($k1 & 0xffff) * 0x1b873593) + (((((($k1 >= 0) ? ($k1 >> 16) : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0x1b873593) & 0xffff) << 16))) & 0xffffffff;
            $h1 ^= $k1;
            $h1 = $h1 << 13 | (($h1 >= 0) ? ($h1 >> 19) : (($h1 & 0x7fffffff) >> 19) | 0x1000);
            $h1b = (((($h1 & 0xffff) * 5) + (((((($h1 >= 0) ? ($h1 >> 16) : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 5) & 0xffff) << 16))) & 0xffffffff;
            $h1 = ((($h1b & 0xffff) + 0x6b64) + (((((($h1b >= 0) ? ($h1b >> 16) : (($h1b & 0x7fffffff) >> 16) | 0x8000)) + 0xe654) & 0xffff) << 16));
        }
    
        $k1 = 0;
    
        switch ($remainder) {
            case 3:
                $k1 ^= $string[$i + 2] << 16;
    
            case 2:
                $k1 ^= $string[$i + 1] << 8;
    
            case 1:
                $k1 ^= $string[$i];
                $k1 = ((($k1 & 0xffff) * 0xcc9e2d51) + (((((($k1 >= 0) ? ($k1 >> 16) : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0xcc9e2d51) & 0xffff) << 16)) & 0xffffffff;
                $k1 = $k1 << 15 | (($k1 >= 0) ? ($k1 >> 17) : (($k1 & 0x7fffffff) >> 17) | 0x4000);
                $k1 = ((($k1 & 0xffff) * 0x1b873593) + (((((($k1 >= 0) ? ($k1 >> 16) : (($k1 & 0x7fffffff) >> 16) | 0x8000)) * 0x1b873593) & 0xffff) << 16)) & 0xffffffff;
                $h1 ^= $k1;
        }
    
        $h1 ^= $klen;
        $h1 ^= (($h1 >= 0) ? ($h1 >> 16) : (($h1 & 0x7fffffff) >> 16) | 0x8000);
        $h1 = ((($h1 & 0xffff) * 0x85ebca6b) + (((((($h1 >= 0) ? ($h1 >> 16) : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 0x85ebca6b) & 0xffff) << 16)) & 0xffffffff;
        $h1 ^= (($h1 >= 0) ? ($h1 >> 13) : (($h1 & 0x7fffffff) >> 13) | 0x40000);
        $h1 = (((($h1 & 0xffff) * 0xc2b2ae35) + (((((($h1 >= 0) ? ($h1 >> 16) : (($h1 & 0x7fffffff) >> 16) | 0x8000)) * 0xc2b2ae35) & 0xffff) << 16))) & 0xffffffff;
        $h1 ^= (($h1 >= 0) ? ($h1 >> 16) : (($h1 & 0x7fffffff) >> 16) | 0x8000);
    
        return base_convert(sprintf("%u\n", $h1), 10, 32);
    }
}