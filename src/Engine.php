<?php

namespace KnifeLemon\CommentTemplate;

use Exception;

/**
 * Engine - A PHP template engine with asset compilation and template inheritance.
 * 
 * Supports the following template directives:
 * - <!--@layout(path)--> : Template inheritance
 * - <!--@import(file)--> : Include other templates
 * - <!--@css(file)--> : Include CSS files
 * - <!--@cssSingle(file)--> : Include single CSS file
 * - <!--@js(file)--> : Include JS files (minified, loaded at before </body>)
 * - <!--@jsAsync(file)--> : Include JS files asynchronously (minified, loaded at before </body>)
 * - <!--@jsDefer(file)--> : Include JS files with defer attribute (minified, loaded at before </body>)
 * - <!--@jsTop(file)--> : Include JS files in head (minified, loaded at before </head>)
 * - <!--@jsTopAsync(file)--> : Include JS files in head asynchronously (minified, loaded at before </head>)
 * - <!--@jsTopDefer(file)--> : Include JS files in head with defer attribute (minified, loaded at before </head>)
 * - <!--@jsSingle(file)--> : Include single JS file (minified, loaded at before </body>)
 * - <!--@jsSingleAsync(file)--> : Include single JS file asynchronously (minified, loaded at before </body>)
 * - <!--@jsSingleDefer(file)--> : Include single JS file with defer attribute (minified, loaded at before </body>)
 * - <!--@base64(file)--> : Encode file as base64 data URI
 * - <!--@asset(file)--> : Copy asset file to public directory
 * - <!--@assetDir(directory)--> : Copy entire directory to public directory
 * - <!--@contents--> : Placeholder for content in layouts
 * - {$variable} : Template variables with filters
 */
class Engine
{
    /**
     * Regular expression patterns for template directives
     */
    const PATTERN = [
        'comment' => '/{\*(.*?)\*}/s',
        'layout' => '/<!--@layout\((.*?)\)-->/',
        'import' => '/<!--@import\((.*?)\)-->/',
        'css' => '/<!--@css\((.*?)\)-->/',
        'cssSingle' => '/<!--@cssSingle\((.*?)\)-->/',
        'js' => '/<!--@js\((.*?)\)-->/',
        'jsAsync' => '/<!--@jsAsync\((.*?)\)-->/',
        'jsDefer' => '/<!--@jsDefer\((.*?)\)-->/',
        'jsTop' => '/<!--@jsTop\((.*?)\)-->/',
        'jsTopAsync' => '/<!--@jsTopAsync\((.*?)\)-->/',
        'jsTopDefer' => '/<!--@jsTopDefer\((.*?)\)-->/',
        'jsSingle' => '/<!--@jsSingle\((.*?)\)-->/',
        'jsSingleAsync' => '/<!--@jsSingleAsync\((.*?)\)-->/',
        'jsSingleDefer' => '/<!--@jsSingleDefer\((.*?)\)-->/',
        'content' => '/<!--@contents-->/',
        'base64' => '/<!--@base64\((.*?)\)-->/',
        'asset' => '/<!--@asset\((.*?)\)-->/',
        'assetDir' => '/<!--@assetDir\((.*?)\)-->/',
        'variables' => '/\{\$(.*?)\}/',
    ];

    /**
     * Available variable commands
     */
    const VARIABLE_COMMAND = [
        'default',
        'concat',
    ];

    private string $publicPath;
    private string $assetPath;
    private string $skinPath;
    private string $fileExtension;
    private ?AssetManager $assetManager = null;
    private bool $hasTracy = false;

    protected int $layoutModifyTime = 0;
    
    /**
     * Constructor
     *
     * @param string $skinPath Path to template files
     * @param string $fileExtension Template file extension
     */
    public function __construct(string $publicPath = "", string $skinPath = "", string $assetPath = "", string $fileExtension = "")
    {
        // if has \Flight Class
        if (class_exists('\Flight')) {
            if ($publicPath === "" && \Flight::has('flight.views.topPath')) {
                $publicPath = \Flight::get('flight.views.topPath');
            }
            if ($skinPath === "" && \Flight::has('flight.views.path')) {
                $skinPath = \Flight::get('flight.views.path');
            }
            if ($assetPath === "" && \Flight::has('flight.views.assetPath')) {
                $assetPath = \Flight::get('flight.views.assetPath');
            }
            if ($fileExtension === "" && \Flight::has('flight.views.extension')) {
                $fileExtension = \Flight::get('flight.views.extension');
            }
        }
        
        // if empty set default
        if ($publicPath === "") {
            $publicPath = __DIR__;
        }
        if ($skinPath === "") {
            $skinPath = __DIR__ . DIRECTORY_SEPARATOR . 'views';
        }
        if ($assetPath === "") {
            $assetPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets';
        }
        if ($fileExtension === "") {
            $fileExtension = '.php';
        }

        $this->publicPath = $publicPath;
        
        if ($this->isAbsolutePath($skinPath)) {
            $this->skinPath = $skinPath;
        } else {
            // Normalize path separators for relative paths
            $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $skinPath);
            $this->skinPath = rtrim($publicPath, '/\\') . DIRECTORY_SEPARATOR . ltrim($normalizedPath, '/\\');
        }
        
        $this->assetPath = $assetPath;
        $this->fileExtension = $fileExtension;

        // Initialize Tracy logger if available
        TemplateLogger::init();
        TemplateLogger::addTracyPanel();
        
        // Log initialization
        TemplateLogger::logInit([
            'publicPath' => $this->publicPath,
            'skinPath' => $this->skinPath,
            'assetPath' => $this->assetPath,
            'fileExtension' => $this->fileExtension,
        ]);
        
        // Initialize AssetManager when paths are set
        if (isset($this->publicPath)) {
            // Use assetPath as full path if it contains directory separators, otherwise as relative to publicPath
            $assetTargetPath = (strpos($this->assetPath, DIRECTORY_SEPARATOR) !== false || strpos($this->assetPath, '/') !== false) 
                ? $this->assetPath 
                : $this->publicPath . DIRECTORY_SEPARATOR . $this->assetPath;
            
            // Calculate web root path for URL generation
            $webRootPath = (strpos($this->assetPath, DIRECTORY_SEPARATOR) !== false || strpos($this->assetPath, '/') !== false) 
                ? basename($this->assetPath)
                : $this->assetPath;
                
            $this->assetManager = new AssetManager($this->skinPath, $assetTargetPath, $webRootPath);
        }
    }
    
    /**
     * Render a template with data
     *
     * @param string $template Template name (without extension)
     * @param array $data Data to pass to template
     * @throws Exception If template file not found
     */
    public function render(string $template, array $data = []): void
    {
        TemplateLogger::logTemplateStart($template, $data);
        
        $templatePath = $this->skinPath . DIRECTORY_SEPARATOR . $template . $this->fileExtension;
        if (file_exists($templatePath)) {
            $templateHTML = $this->convertTemplate($templatePath, $data);
            $this->convertVariable($templateHTML, $data);
            
            TemplateLogger::logTemplateEnd($template, strlen($templateHTML));
            TemplateLogger::logSummary();
            
            echo $templateHTML;
        } else {
            throw new Exception("Template file not found: " . $templatePath);
        }
    }

    /**
     * Get rendered template as string instead of echoing
     *
     * @param string $template Template name (without extension)
     * @param array $data Data to pass to template
     * @return string Rendered HTML
     * @throws Exception If template file not found
     */
    public function fetch(string $template, array $data = []): string
    {
        ob_start();
        $this->render($template, $data);
        return ob_get_clean();
    }
    
    /**
     * Convert template with all directives
     *
     * @param string $templatePath Full path to template file
     * @param array $data Template data
     * @return string Processed HTML
     * @throws Exception If layout or import files not found
     */
    private function convertTemplate(string $templatePath, array $data): string
    {
        // data extraction
        extract($data);

        // load template file
        ob_start();
        include $templatePath;
        $html = ob_get_clean();

        // layout pattern
        if (preg_match(self::PATTERN['layout'], $html, $matches)) {
            $layout = $matches[1];
            $layoutPath = $this->skinPath . DIRECTORY_SEPARATOR . $layout . $this->fileExtension;
            if (file_exists($layoutPath)) {
                // Log layout usage
                TemplateLogger::logLayout($layout, $layoutPath);
                
                // save layout modification time
                $this->layoutModifyTime = filemtime($layoutPath);

                ob_start();
                include $layoutPath;
                $layoutHtml = ob_get_clean();

                // remove layout comment
                $html = str_replace($matches[0], '', $html);

                // insert content into layout
                if (preg_match(self::PATTERN['content'], $layoutHtml)) {
                    $html = preg_replace(self::PATTERN['content'], $html, $layoutHtml);
                } else {
                    throw new Exception("Content Comment not has " . $layoutPath . " file\n" . "need \"" . str_replace('/', '', self::PATTERN['content']) . "\" comment in layout file");
                }
            } else {
                throw new Exception("Layout file not found: " . $layoutPath);
            }
        }

        // 임포트 패턴
        if (preg_match_all(self::PATTERN['import'], $html, $matches)) {
            foreach ($matches[1] as $index => $import) {
                $importPath = $this->skinPath . DIRECTORY_SEPARATOR . $import . $this->fileExtension;
                if (file_exists($importPath)) {
                    // Log import
                    TemplateLogger::logImport($import, $importPath);
                    
                    ob_start();
                    include $importPath;
                    $importHtml = ob_get_clean();

                    $html = str_replace($matches[0][$index], $importHtml, $html);
                } else {
                    throw new Exception("Import file not found: " . $importPath);
                }
            }
        }

        // 주석 제거
        $html = preg_replace(self::PATTERN['comment'], '', $html);

        // Asset 파일을 컴파일하고 HTML에 추가합니다.
        $assetCompiler = new AssetCompiler($this->publicPath, $this->skinPath, $this->layoutModifyTime, $this->assetPath);
        
        $assetCompiler->compileAssets(AssetType::CSS_SINGLE, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::CSS, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::JS_TOP, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::JS_TOP_ASYNC, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::JS_TOP_DEFER, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::JS_SINGLE, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::JS_SINGLE_ASYNC, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::JS_SINGLE_DEFER, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::JS, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::JS_ASYNC, $templatePath, $html, self::PATTERN);
        $assetCompiler->compileAssets(AssetType::JS_DEFER, $templatePath, $html, self::PATTERN);
        
        $this->encodeBase64($html);
        $this->processAssets($html);
        
        // Clean up empty lines left by directive removal
        $html = preg_replace('/^\s*\n/m', '', $html);
        $html = preg_replace('/\n\s*\n/', "\n", $html);
        $html = trim($html);

        return $html;
    }

    /**
     * Convert template variables with filters and commands
     *
     * @param string $html HTML content to process
     * @param array $data Template data
     */
    private function convertVariable(string &$html, array $data): void
    {
        if (preg_match_all(self::PATTERN['variables'], $html, $matches)) {
            foreach ($matches[1] as $variable) {
                $expValue = explode('|', $variable);
                $originalValue = $data[$expValue[0]] ?? '';
                $value = $originalValue;
                $filters = [];

                if (count($expValue) > 1) {
                    // for loop
                    for ($i = 1; $i < count($expValue); $i++) { 
                        $exp = $expValue[$i];

                        $exp = trim($exp);
                        // command
                        if (strpos($exp, '=') !== false && array_search(explode('=', $exp)[0], self::VARIABLE_COMMAND) !== false) {
                            $exp = explode('=', $exp);
                            if (count($exp) != 2) {
                                continue;
                            }
                            $command = $exp[0];
                            $commentValue = $exp[1];
                            $filters[] = $command;

                            switch ($command) {
                                case 'default':
                                    if (strlen($value) == 0) {
                                        $value = $commentValue;
                                    }
                                    break;
                                case 'concat':
                                    $value .= $commentValue;
                                    break;
                                default:
                                    break;
                            }
                        // function
                        } else if (VariableFunction::hasFunction($exp)) {
                            $filters[] = $exp;
                            $value = VariableFunction::$exp($value);
                        }
                    }
                }
                
                // Log variable usage with original and transformed values
                // Always pass transformed value if filters were applied, even if the result is the same
                $transformedValue = !empty($filters) ? $value : null;
                TemplateLogger::logVariable($expValue[0], $originalValue, $filters, $transformedValue);
                
                $html = str_replace('{$' . $variable . '}', $value, $html);
            }
        }
    }

    /**
     * Encode files as base64 data URIs
     *
     * @param string $html HTML content to process
     */
    private function encodeBase64(string &$html): void
    {
        if (preg_match_all(self::PATTERN['base64'], $html, $matches)) {
            foreach ($matches[1] as $path) {
                $realPath = $this->skinPath . DIRECTORY_SEPARATOR . $path;
                $fileSize = file_exists($realPath) ? filesize($realPath) : 0;
                
                if ($this->assetManager) {
                    $base64 = $this->assetManager->processBase64($path);
                    if ($base64) {
                        // Log base64 encoding
                        if ($fileSize > 0) {
                            TemplateLogger::logBase64($path, $fileSize);
                        }
                        $html = str_replace("<!--@base64($path)-->", $base64, $html);
                    }
                } else {
                    // Fallback to original method
                    if (file_exists($realPath)) {
                        $data = file_get_contents($realPath);
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $realPath);
                        finfo_close($finfo);
                        $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($data);
                        
                        // Log base64 encoding
                        TemplateLogger::logBase64($path, $fileSize);
                        
                        $html = str_replace("<!--@base64($path)-->", $base64, $html);
                    }
                }
            }
        }
    }

    /**
     * Process asset directives and copy files to public directory
     *
     * @param string $html HTML content to process
     */
    private function processAssets(string &$html): void
    {
        // Process @assetDir directive (directories) - copy only, no output
        if (preg_match_all(self::PATTERN['assetDir'], $html, $matches)) {
            foreach ($matches[1] as $index => $path) {
                if ($this->assetManager) {
                    $sourcePath = $this->skinPath . DIRECTORY_SEPARATOR . $path;
                    $destination = $this->assetManager->copyAsset($path);
                    
                    // Log asset directory copy
                    TemplateLogger::logAssetCopy($sourcePath, $destination, true);
                    
                    $this->assetManager->copyAsset($path);
                }
                // Remove the directive from HTML (assetDir is for copying only, no output)
                $html = str_replace($matches[0][$index], '', $html);
            }
        }

        // Process @asset directive (single files)
        if (preg_match_all(self::PATTERN['asset'], $html, $matches)) {
            foreach ($matches[1] as $index => $path) {
                if ($this->assetManager) {
                    $sourcePath = $this->skinPath . DIRECTORY_SEPARATOR . $path;
                    $publicUrl = $this->assetManager->copyAsset($path);
                    
                    // Log asset copy
                    TemplateLogger::logAssetCopy($sourcePath, $publicUrl, false);
                    
                    $html = str_replace($matches[0][$index], $publicUrl, $html);
                } else {
                    // Fallback: just return the path as-is
                    $html = str_replace($matches[0][$index], '/' . ltrim($path, '/'), $html);
                }
            }
        }
    }

    /**
     * Set public path for asset compilation
     *
     * @param string $path Public path
     */
    public function setPublicPath(string $path): void
    {
        $this->publicPath = $path;
        
        // Initialize or update AssetManager if asset path is already set
        if (isset($this->skinPath) && isset($this->assetPath)) {
            $assetTargetPath = $this->resolveAssetPath($this->assetPath);
            $webRootPath = $this->isAbsolutePath($this->assetPath) 
                ? basename($this->assetPath)
                : $this->assetPath;
                
            $this->assetManager = new AssetManager($this->skinPath, $assetTargetPath, $webRootPath);
        }
    }

    /**
     * Set asset path for asset storage (supports both relative and absolute paths)
     *
     * @param string $path Asset path 
     *                     - Relative: 'assets', 'static/files' (relative to public path)
     *                     - Absolute: '/var/www/assets', 'C:\www\assets'
     */
    public function setAssetPath(string $path): void
    {
        $this->assetPath = $path;
        
        // Update AssetManager if it exists
        if (isset($this->skinPath) && isset($this->publicPath)) {
            $assetTargetPath = $this->resolveAssetPath($this->assetPath);
            $webRootPath = $this->isAbsolutePath($this->assetPath) 
                ? basename($this->assetPath)
                : $this->assetPath;
                
            $this->assetManager = new AssetManager($this->skinPath, $assetTargetPath, $webRootPath);
        }
    }

    /**
     * Check if a path is absolute
     *
     * @param string $path Path to check
     * @return bool True if absolute path, false if relative
     */
    private function isAbsolutePath(string $path): bool
    {
        // Unix/Linux absolute path (starts with /)
        if (str_starts_with($path, '/')) {
            return true;
        }
        
        // Windows absolute path (starts with drive letter like C:\)
        if (preg_match('/^[a-zA-Z]:[\\\\\/]/', $path)) {
            return true;
        }
        
        // UNC path (starts with \\)
        if (str_starts_with($path, '\\\\')) {
            return true;
        }
        
        return false;
    }

    /**
     * Resolve asset path (relative to public path or absolute)
     *
     * @param string $assetPath Asset path to resolve
     * @return string Resolved absolute path
     */
    private function resolveAssetPath(string $assetPath): string
    {
        if ($this->isAbsolutePath($assetPath)) {
            return $assetPath;
        }
        
        // Relative path - combine with public path
        return rtrim($this->publicPath, '/\\') . DIRECTORY_SEPARATOR . ltrim($assetPath, '/\\');
    }

    /**
     * Set skin/template path (supports both relative and absolute paths)
     *
     * @param string $path Template path
     *                     - Relative: 'views', 'templates/pages' (relative to public path)
     *                     - Absolute: '/var/www/templates', 'C:\www\templates'
     */
    public function setSkinPath(string $path): void
    {
        if ($this->isAbsolutePath($path)) {
            $this->skinPath = $path;
        } else {
            // Normalize path separators for relative paths
            $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            $this->skinPath = rtrim($this->publicPath, '/\\') . DIRECTORY_SEPARATOR . ltrim($normalizedPath, '/\\');
        }
            
        // Update AssetManager if it exists
        if (isset($this->assetManager) && isset($this->assetPath)) {
            $assetTargetPath = $this->resolveAssetPath($this->assetPath);
            $webRootPath = $this->isAbsolutePath($this->assetPath) 
                ? basename($this->assetPath)
                : $this->assetPath;
                
            $this->assetManager = new AssetManager($this->skinPath, $assetTargetPath, $webRootPath);
        }
    }



    /**
     * Set template file extension
     *
     * @param string $extension File extension
     */
    public function setFileExtension(string $extension): void
    {
        $this->fileExtension = $extension;
    }

    /**
     * Get public path
     *
     * @return string Public path
     */
    public function getPublicPath(): string
    {
        return $this->publicPath;
    }

    /**
     * Get skin/template path
     *
     * @return string Template path
     */
    public function getSkinPath(): string
    {
        return $this->skinPath;
    }

    /**
     * Get template file extension
     *
     * @return string File extension
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * Get asset path
     *
     * @return string Asset path
     */
    public function getAssetPath(): string
    {
        return $this->assetPath;
    }
}