<?php

namespace KnifeLemon\CommentTemplate;

/**
 * TemplateLogger - Logging utility for CommentTemplate with Tracy integration
 * 
 * Provides detailed logging of template rendering process including:
 * - Initialization info (paths, configuration)
 * - Asset compilation details (CSS/JS files, sizes)
 * - Variable usage tracking
 * - Performance metrics
 */
class TemplateLogger
{
    private static bool $enabled = false;
    private static array $logs = [];
    private static float $startTime = 0;
    private static array $metrics = [
        'templates_rendered' => 0,
        'layouts_used' => 0,
        'imports_count' => 0,
        'css_compiled' => 0,
        'js_compiled' => 0,
        'assets_copied' => 0,
        'variables_used' => [],
    ];

    /**
     * Initialize logger and check if Tracy is available
     */
    public static function init(): void
    {
        self::$enabled = class_exists('\Tracy\Debugger');
        self::$startTime = microtime(true);
    }

    /**
     * Log initialization information
     */
    public static function logInit(array $config): void
    {
        if (!self::$enabled) return;

        $info = [
            'timestamp' => date('Y-m-d H:i:s'),
            'public_path' => $config['publicPath'] ?? 'not set',
            'skin_path' => $config['skinPath'] ?? 'not set',
            'asset_path' => $config['assetPath'] ?? 'not set',
            'file_extension' => $config['fileExtension'] ?? 'not set',
            'memory_usage' => self::formatBytes(memory_get_usage()),
            'peak_memory' => self::formatBytes(memory_get_peak_usage()),
        ];

        self::$logs[] = [
            'type' => 'init',
            'message' => 'Template engine initialized',
            'data' => $info,
        ];
    }

    /**
     * Log template rendering start
     */
    public static function logTemplateStart(string $template, array $data): void
    {
        if (!self::$enabled) return;

        self::$metrics['templates_rendered']++;

        $info = [
            'template' => $template,
            'variables_count' => count($data),
            'variables' => array_keys($data),
            'time' => microtime(true),
        ];

        self::$logs[] = [
            'type' => 'template_start',
            'message' => "Rendering template: {$template}",
            'data' => $info,
        ];
    }

    /**
     * Log layout usage
     */
    public static function logLayout(string $layout, string $layoutPath): void
    {
        if (!self::$enabled) return;

        self::$metrics['layouts_used']++;

        $info = [
            'layout' => $layout,
            'path' => $layoutPath,
            'exists' => file_exists($layoutPath),
            'size' => file_exists($layoutPath) ? self::formatBytes(filesize($layoutPath)) : 'N/A',
        ];

        self::$logs[] = [
            'type' => 'layout',
            'message' => "Using layout: {$layout}",
            'data' => $info,
        ];
    }

    /**
     * Log template import
     */
    public static function logImport(string $import, string $importPath): void
    {
        if (!self::$enabled) return;

        self::$metrics['imports_count']++;

        $info = [
            'import' => $import,
            'path' => $importPath,
            'exists' => file_exists($importPath),
            'size' => file_exists($importPath) ? self::formatBytes(filesize($importPath)) : 'N/A',
        ];

        self::$logs[] = [
            'type' => 'import',
            'message' => "Importing template: {$import}",
            'data' => $info,
        ];
    }

    /**
     * Log CSS compilation
     */
    public static function logCssCompilation(array $files, int $originalSize, int $minifiedSize, string $outputPath, bool $isSingle = false): void
    {
        if (!self::$enabled) return;

        self::$metrics['css_compiled']++;

        $compressionRatio = $originalSize > 0 ? round((1 - $minifiedSize / $originalSize) * 100, 2) : 0;

        $info = [
            'files' => $files,
            'file_count' => count($files),
            'original_size' => self::formatBytes($originalSize),
            'minified_size' => self::formatBytes($minifiedSize),
            'compression_ratio' => $compressionRatio . '%',
            'saved' => self::formatBytes($originalSize - $minifiedSize),
            'output' => $outputPath,
            'is_single' => $isSingle,
        ];

        self::$logs[] = [
            'type' => 'css_compile',
            'message' => 'CSS files compiled',
            'data' => $info,
        ];
    }

    /**
     * Log JS compilation
     */
    public static function logJsCompilation(array $files, int $originalSize, int $minifiedSize, string $outputPath, array $options = [], bool $isSingle = false): void
    {
        if (!self::$enabled) return;

        self::$metrics['js_compiled']++;

        $compressionRatio = $originalSize > 0 ? round((1 - $minifiedSize / $originalSize) * 100, 2) : 0;

        $info = [
            'files' => $files,
            'file_count' => count($files),
            'original_size' => self::formatBytes($originalSize),
            'minified_size' => self::formatBytes($minifiedSize),
            'compression_ratio' => $compressionRatio . '%',
            'saved' => self::formatBytes($originalSize - $minifiedSize),
            'output' => $outputPath,
            'async' => $options['async'] ?? false,
            'defer' => $options['defer'] ?? false,
            'position' => $options['position'] ?? 'bottom',
            'is_single' => $isSingle,
        ];

        self::$logs[] = [
            'type' => 'js_compile',
            'message' => 'JS files compiled',
            'data' => $info,
        ];
    }

    /**
     * Log asset copying
     */
    public static function logAssetCopy(string $source, string $destination, bool $isDirectory = false): void
    {
        if (!self::$enabled) return;

        self::$metrics['assets_copied']++;

        $info = [
            'source' => $source,
            'destination' => $destination,
            'type' => $isDirectory ? 'directory' : 'file',
            'exists' => file_exists($source),
            'size' => !$isDirectory && file_exists($source) ? self::formatBytes(filesize($source)) : 'N/A',
        ];

        self::$logs[] = [
            'type' => 'asset_copy',
            'message' => $isDirectory ? "Copying directory: {$source}" : "Copying asset: {$source}",
            'data' => $info,
        ];
    }

    /**
     * Log base64 encoding
     */
    public static function logBase64(string $file, int $size): void
    {
        if (!self::$enabled) return;

        $info = [
            'file' => $file,
            'original_size' => self::formatBytes($size),
            'base64_size' => self::formatBytes(ceil($size * 4 / 3)),
        ];

        self::$logs[] = [
            'type' => 'base64',
            'message' => "Base64 encoding: {$file}",
            'data' => $info,
        ];
    }

    /**
     * Log variable usage
     */
    public static function logVariable(string $variable, $originalValue, array $filters = [], $transformedValue = null): void
    {
        if (!self::$enabled) return;

        $varKey = $variable;
        if (!isset(self::$metrics['variables_used'][$varKey])) {
            self::$metrics['variables_used'][$varKey] = [
                'count' => 0,
                'filters' => [],
                'original_value' => self::getValuePreview($originalValue),
                'type' => gettype($originalValue),
            ];
        }

        self::$metrics['variables_used'][$varKey]['count']++;
        if (!empty($filters)) {
            self::$metrics['variables_used'][$varKey]['filters'] = array_unique(
                array_merge(self::$metrics['variables_used'][$varKey]['filters'], $filters)
            );
        }
        
        // Store transformed value if provided
        if ($transformedValue !== null && !isset(self::$metrics['variables_used'][$varKey]['transformed_value'])) {
            self::$metrics['variables_used'][$varKey]['transformed_value'] = self::getValuePreview($transformedValue);
        }

        $info = [
            'variable' => $variable,
            'type' => gettype($originalValue),
            'filters' => $filters,
            'original_value' => self::getValuePreview($originalValue),
            'transformed_value' => $transformedValue !== null ? self::getValuePreview($transformedValue) : null,
        ];

        self::$logs[] = [
            'type' => 'variable',
            'message' => "Variable used: {$variable}",
            'data' => $info,
        ];
    }

    /**
     * Log template rendering completion
     */
    public static function logTemplateEnd(string $template, int $outputSize): void
    {
        if (!self::$enabled) return;

        $duration = microtime(true) - self::$startTime;

        $info = [
            'template' => $template,
            'output_size' => self::formatBytes($outputSize),
            'duration' => round($duration * 1000, 2) . 'ms',
            'memory_usage' => self::formatBytes(memory_get_usage()),
            'peak_memory' => self::formatBytes(memory_get_peak_usage()),
        ];

        self::$logs[] = [
            'type' => 'template_end',
            'message' => "Template rendering completed: {$template}",
            'data' => $info,
        ];
    }

    /**
     * Log performance summary
     */
    public static function logSummary(): void
    {
        if (!self::$enabled) return;

        $duration = microtime(true) - self::$startTime;

        $summary = [
            'total_duration' => round($duration * 1000, 2) . 'ms',
            'templates_rendered' => self::$metrics['templates_rendered'],
            'layouts_used' => self::$metrics['layouts_used'],
            'imports_count' => self::$metrics['imports_count'],
            'css_files_compiled' => self::$metrics['css_compiled'],
            'js_files_compiled' => self::$metrics['js_compiled'],
            'assets_copied' => self::$metrics['assets_copied'],
            'unique_variables' => count(self::$metrics['variables_used']),
            'total_variable_usages' => array_sum(array_column(self::$metrics['variables_used'], 'count')),
            'memory_peak' => self::formatBytes(memory_get_peak_usage()),
            'total_logs' => count(self::$logs),
        ];
    }

    /**
     * Get all logs
     */
    public static function getLogs(): array
    {
        return self::$logs;
    }

    /**
     * Get metrics
     */
    public static function getMetrics(): array
    {
        return self::$metrics;
    }

    /**
     * Check if logging is enabled
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Add Tracy Bar Panel
     */
    public static function addTracyPanel(): void
    {
        if (!self::$enabled) return;

        if (class_exists('\Tracy\Debugger')) {
            \Tracy\Debugger::getBar()->addPanel(new CommentTemplatePanel());
        }
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get value preview for logging
     */
    private static function getValuePreview($value): string
    {
        if (is_string($value)) {
            return strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
        }
        if (is_array($value)) {
            return 'Array(' . count($value) . ')';
        }
        if (is_object($value)) {
            return get_class($value);
        }
        return (string)$value;
    }

    /**
     * Reset logger state
     */
    public static function reset(): void
    {
        self::$logs = [];
        self::$startTime = microtime(true);
        self::$metrics = [
            'templates_rendered' => 0,
            'layouts_used' => 0,
            'imports_count' => 0,
            'css_compiled' => 0,
            'js_compiled' => 0,
            'assets_copied' => 0,
            'variables_used' => [],
        ];
    }
}
