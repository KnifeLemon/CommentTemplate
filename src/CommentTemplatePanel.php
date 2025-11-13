<?php

namespace KnifeLemon\CommentTemplate;

use Tracy\IBarPanel;

/**
 * CommentTemplatePanel - Custom Tracy Bar Panel for CommentTemplate
 * 
 * Displays comprehensive logging information in Tracy debug bar
 */
class CommentTemplatePanel implements IBarPanel
{
    /**
     * Renders HTML code for custom tab
     */
    public function getTab(): string
    {
        if (!TemplateLogger::isEnabled()) {
            return '';
        }

        $metrics = TemplateLogger::getMetrics();
        $totalOps = $metrics['templates_rendered'] + $metrics['css_compiled'] + $metrics['js_compiled'];

        return '
        <span title="CommentTemplate">
            <svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 0H16V13.6H0V0Z" fill="#BBDEFB"/>
                <path d="M2.00006 2H14.0001V4H2.00006V2Z" fill="#3F51B5"/>
                <path d="M2.00006 5.59961H7.20006V11.9996H2.00006V5.59961ZM8.80006 5.59961H14.0001V11.9996H8.80006V5.59961Z" fill="#2196F3"/>
            </svg>
            <span class="tracy-label">CommentTemplate</span>
        </span>';
    }

    /**
     * Renders HTML code for custom panel
     */
    public function getPanel(): string
    {
        if (!TemplateLogger::isEnabled()) {
            return '<h1>CommentTemplate</h1><p>Logger not enabled</p>';
        }

        $logs = TemplateLogger::getLogs();
        $metrics = TemplateLogger::getMetrics();

        $html = $this->renderStyles();
        $html .= '<h1>CommentTemplate Performance</h1>';
        $html .= '<div class="ct-panel">';
        
        // Summary Cards
        $html .= $this->renderSummaryCards($metrics);
        
        // Tabs
        $html .= '<div class="ct-tabs">';
        $html .= '<button class="ct-tab active" data-tab="overview">Overview</button>';
        $html .= '<button class="ct-tab" data-tab="assets">Assets</button>';
        $html .= '<button class="ct-tab" data-tab="variables">Variables</button>';
        $html .= '<button class="ct-tab" data-tab="timeline">Timeline</button>';
        $html .= '</div>';
        
        // Tab Contents
        $html .= '<div class="ct-content">';
        $html .= $this->renderOverviewTab($metrics);
        $html .= $this->renderAssetsTab($logs, $metrics);
        $html .= $this->renderVariablesTab($metrics);
        $html .= $this->renderTimelineTab($logs);
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= $this->renderScripts();

        return $html;
    }

    /**
     * Render custom styles
     */
    private function renderStyles(): string
    {
        return <<<'HTML'
        <style>
            .ct-panel {
                display: flex;
                flex-direction: column;
                min-width: 600px;
                max-width: 900px;
                margin-top: 10px;
            }
            .ct-summary {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
                gap: 10px;
                margin-bottom: 20px;
            }
            .ct-card {
                background: #f8f9fa;
                border-left: 4px solid #2196F3;
                padding: 12px;
                border-radius: 4px;
            }
            .ct-card-title {
                font-size: 11px;
                color: #666;
                text-transform: uppercase;
                margin-bottom: 5px;
            }
            .ct-card-value {
                font-size: 20px;
                font-weight: bold;
                color: #333;
            }
            .ct-card-subtitle {
                font-size: 11px;
                color: #999;
                margin-top: 3px;
            }
            .ct-tabs {
                display: flex;
                border-bottom: 2px solid #e0e0e0;
                margin-bottom: 15px;
            }
            .ct-tab {
                background: none;
                border: none;
                padding: 10px 20px;
                cursor: pointer;
                font-size: 13px;
                color: #666;
                border-bottom: 3px solid transparent;
                transition: all 0.2s;
            }
            .ct-tab:hover {
                color: #2196F3;
                background: #f5f5f5;
            }
            .ct-tab.active {
                color: #2196F3;
                border-bottom-color: #2196F3;
                font-weight: 600;
            }
            .ct-content {
                min-height: 270px;
                overflow-y: auto;
                flex: 1;
            }
            .ct-tab-pane {
                display: none;
            }
            .ct-tab-pane.active {
                display: block;
            }
            .ct-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 12px;
            }
            .ct-table th {
                background: #f5f5f5;
                padding: 8px;
                text-align: left;
                font-weight: 600;
                border-bottom: 2px solid #e0e0e0;
                position: sticky;
                top: 0;
            }
            .ct-table td {
                padding: 8px;
                border-bottom: 1px solid #f0f0f0;
            }
            .ct-table tr:hover {
                background: #fafafa;
            }
            .ct-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }
            .ct-badge-success {
                background: #e8f5e9;
                color: #2e7d32;
            }
            .ct-badge-info {
                background: #e3f2fd;
                color: #1565c0;
            }
            .ct-badge-warning {
                background: #fff3e0;
                color: #e65100;
            }
            .ct-timeline {
                position: relative;
                padding-left: 30px;
            }
            .ct-timeline-item {
                position: relative;
                padding-bottom: 20px;
                border-left: 2px solid #e0e0e0;
                padding-left: 20px;
                margin-left: 10px;
            }
            .ct-timeline-item::before {
                content: '';
                position: absolute;
                left: -6px;
                top: 0;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: #2196F3;
            }
            .ct-timeline-time {
                font-size: 11px;
                color: #999;
            }
            .ct-timeline-title {
                font-weight: 600;
                margin: 5px 0;
            }
            .ct-timeline-detail {
                font-size: 11px;
                color: #666;
            }
            .ct-stat-row {
                display: flex;
                justify-content: space-between;
                padding: 10px;
                border-bottom: 1px solid #f0f0f0;
            }
            .ct-stat-label {
                color: #666;
                font-weight: 600;
            }
            .ct-stat-value {
                color: #333;
            }
            .ct-progress {
                height: 20px;
                background: #f5f5f5;
                border-radius: 10px;
                overflow: hidden;
                margin-top: 5px;
            }
            .ct-progress-bar {
                height: 100%;
                background: linear-gradient(90deg, #2196F3, #21CBF3);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 11px;
                font-weight: 600;
            }
            .ct-code {
                background: #f5f5f5;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: monospace;
                font-size: 11px;
            }
        </style>
HTML;
    }

    /**
     * Render summary cards
     */
    private function renderSummaryCards(array $metrics): string
    {
        $logs = TemplateLogger::getLogs();
        $duration = 0;
        foreach ($logs as $log) {
            if ($log['type'] === 'template_end' && isset($log['data']['duration'])) {
                $duration = $log['data']['duration'];
            }
        }

        $html = '<div class="ct-summary">';
        
        $html .= '<div class="ct-card">';
        $html .= '<div class="ct-card-title">Duration</div>';
        $html .= '<div class="ct-card-value">' . $duration . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="ct-card">';
        $html .= '<div class="ct-card-title">Templates</div>';
        $html .= '<div class="ct-card-value">' . $metrics['templates_rendered'] . '</div>';
        $html .= '<div class="ct-card-subtitle">' . $metrics['layouts_used'] . ' layouts, ' . $metrics['imports_count'] . ' imports</div>';
        $html .= '</div>';
        
        $html .= '<div class="ct-card">';
        $html .= '<div class="ct-card-title">Assets</div>';
        $html .= '<div class="ct-card-value">' . ($metrics['css_compiled'] + $metrics['js_compiled']) . '</div>';
        $html .= '<div class="ct-card-subtitle">' . $metrics['css_compiled'] . ' CSS, ' . $metrics['js_compiled'] . ' JS</div>';
        $html .= '</div>';
        
        $html .= '<div class="ct-card">';
        $html .= '<div class="ct-card-title">Variables</div>';
        $html .= '<div class="ct-card-value">' . count($metrics['variables_used']) . '</div>';
        $totalUsages = array_sum(array_column($metrics['variables_used'], 'count'));
        $html .= '<div class="ct-card-subtitle">' . $totalUsages . ' total usages</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render overview tab
     */
    private function renderOverviewTab(array $metrics): string
    {
        $logs = TemplateLogger::getLogs();
        $html = '<div class="ct-tab-pane active" data-pane="overview">';
        
        foreach ($logs as $log) {
            if ($log['type'] === 'init' && isset($log['data'])) {
                $html .= '<h3>Configuration</h3>';
                $html .= '<div class="ct-stat-row"><span class="ct-stat-label">Public Path</span><span class="ct-code">' . htmlspecialchars($log['data']['public_path']) . '</span></div>';
                $html .= '<div class="ct-stat-row"><span class="ct-stat-label">Templates Path</span><span class="ct-code">' . htmlspecialchars($log['data']['skin_path']) . '</span></div>';
                $html .= '<div class="ct-stat-row"><span class="ct-stat-label">Asset Path</span><span class="ct-code">' . htmlspecialchars($log['data']['asset_path']) . '</span></div>';
                $html .= '<div class="ct-stat-row"><span class="ct-stat-label">File Extension</span><span class="ct-code">' . htmlspecialchars($log['data']['file_extension']) . '</span></div>';
            }
            
            if ($log['type'] === 'template_end' && isset($log['data'])) {
                $html .= '<h3 style="margin-top: 20px;">Performance</h3>';
                $html .= '<div class="ct-stat-row"><span class="ct-stat-label">Output Size</span><span class="ct-stat-value">' . $log['data']['output_size'] . '</span></div>';
                $html .= '<div class="ct-stat-row"><span class="ct-stat-label">Memory Usage</span><span class="ct-stat-value">' . $log['data']['memory_usage'] . '</span></div>';
                $html .= '<div class="ct-stat-row"><span class="ct-stat-label">Peak Memory</span><span class="ct-stat-value">' . $log['data']['peak_memory'] . '</span></div>';
            }
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render assets tab
     */
    private function renderAssetsTab(array $logs, array $metrics): string
    {
        $html = '<div class="ct-tab-pane" data-pane="assets">';
        
        // CSS Compilation
        $cssLogs = array_filter($logs, fn($log) => $log['type'] === 'css_compile');
        if (!empty($cssLogs)) {
            $html .= '<h3>CSS Files</h3>';
            $html .= '<table class="ct-table">';
            $html .= '<thead><tr><th>Type</th><th>Files</th><th>Original</th><th>Processed</th><th>Saved</th><th>Ratio</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($cssLogs as $log) {
                $data = $log['data'];
                $isSingle = $data['is_single'] ?? false;
                
                $html .= '<tr>';
                // Type column
                if ($isSingle) {
                    $html .= '<td><span class="ct-badge ct-badge-warning">Single</span></td>';
                } else {
                    $html .= '<td><span class="ct-badge ct-badge-info">Combined</span></td>';
                }
                $html .= '<td><span class="ct-badge ct-badge-info">' . $data['file_count'] . ' file' . ($data['file_count'] > 1 ? 's' : '') . '</span></td>';
                $html .= '<td>' . $data['original_size'] . '</td>';
                $html .= '<td>' . $data['minified_size'] . '</td>';
                $html .= '<td>' . $data['saved'] . '</td>';
                $html .= '<td><span class="ct-badge ct-badge-success">' . $data['compression_ratio'] . '</span></td>';
                $html .= '</tr>';
                $html .= '<tr><td colspan="6" style="font-size: 11px; color: #666;">';
                foreach ($data['files'] as $file) {
                    $html .= '<div style="padding: 2px 0;">📄 ' . htmlspecialchars(basename($file)) . '</div>';
                }
                $html .= '</td></tr>';
            }
            $html .= '</tbody></table>';
        }
        
        // JS Compilation
        $jsLogs = array_filter($logs, fn($log) => $log['type'] === 'js_compile');
        if (!empty($jsLogs)) {
            $html .= '<h3 style="margin-top: 20px;">JS Files</h3>';
            $html .= '<table class="ct-table">';
            $html .= '<thead><tr><th>Type</th><th>Files</th><th>Original</th><th>Processed</th><th>Saved</th><th>Options</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($jsLogs as $log) {
                $data = $log['data'];
                $isSingle = $data['is_single'] ?? false;
                
                $html .= '<tr>';
                // Type column
                if ($isSingle) {
                    $html .= '<td><span class="ct-badge ct-badge-warning">Single</span></td>';
                } else {
                    $html .= '<td><span class="ct-badge ct-badge-info">Combined</span></td>';
                }
                $html .= '<td><span class="ct-badge ct-badge-info">' . $data['file_count'] . ' file' . ($data['file_count'] > 1 ? 's' : '') . '</span></td>';
                $html .= '<td>' . $data['original_size'] . '</td>';
                $html .= '<td>' . $data['minified_size'] . '</td>';
                $html .= '<td>' . $data['saved'] . '</td>';
                $html .= '<td>';
                if ($data['async']) $html .= '<span class="ct-badge ct-badge-warning">async</span> ';
                if ($data['defer']) $html .= '<span class="ct-badge ct-badge-warning">defer</span> ';
                $html .= '<span class="ct-badge ct-badge-info">' . $data['position'] . '</span>';
                $html .= '</td>';
                $html .= '</tr>';
                $html .= '<tr><td colspan="6" style="font-size: 11px; color: #666;">';
                foreach ($data['files'] as $file) {
                    $html .= '<div style="padding: 2px 0;">📄 ' . htmlspecialchars(basename($file)) . '</div>';
                }
                $html .= '</td></tr>';
            }
            $html .= '</tbody></table>';
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render variables tab
     */
    private function renderVariablesTab(array $metrics): string
    {
        $html = '<div class="ct-tab-pane" data-pane="variables">';
        
        if (!empty($metrics['variables_used'])) {
            $html .= '<table class="ct-table">';
            $html .= '<thead><tr><th>Variable</th><th>Usage Count</th><th>Original Value</th><th>Transformed Value</th><th>Filters</th></tr></thead>';
            $html .= '<tbody>';
            
            // Sort by usage count
            $variables = $metrics['variables_used'];
            uasort($variables, fn($a, $b) => $b['count'] - $a['count']);
            
            foreach ($variables as $varName => $varData) {
                $html .= '<tr>';
                $html .= '<td><span class="ct-code">{$' . htmlspecialchars($varName) . '}</span></td>';
                $html .= '<td><span class="ct-badge ct-badge-info">' . $varData['count'] . 'x</span></td>';
                
                // Original value
                $html .= '<td>';
                if (isset($varData['original_value']) && $varData['original_value'] !== null) {
                    $html .= '<span class="ct-code" style="background: #fff3e0;">' . htmlspecialchars((string)$varData['original_value']) . '</span>';
                } else {
                    $html .= '<span style="color: #999;">-</span>';
                }
                $html .= '</td>';
                
                // Transformed value
                $html .= '<td>';
                if (isset($varData['transformed_value']) && $varData['transformed_value'] !== null) {
                    $html .= '<span class="ct-code" style="background: #e3f2fd;">' . htmlspecialchars((string)$varData['transformed_value']) . '</span>';
                } else {
                    $html .= '<span style="color: #999;">-</span>';
                }
                $html .= '</td>';
                
                // Filters
                $html .= '<td>';
                if (!empty($varData['filters'])) {
                    foreach ($varData['filters'] as $filter) {
                        $html .= '<span class="ct-badge ct-badge-success">' . htmlspecialchars($filter) . '</span> ';
                    }
                } else {
                    $html .= '<span style="color: #999;">none</span>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        } else {
            $html .= '<p style="color: #999; text-align: center; padding: 40px;">No variables used</p>';
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render timeline tab
     */
    private function renderTimelineTab(array $logs): string
    {
        $html = '<div class="ct-tab-pane" data-pane="timeline">';
        $html .= '<div class="ct-timeline">';
        
        // Get first log time for relative timing
        $startTime = null;
        foreach ($logs as $log) {
            if (isset($log['data']['time'])) {
                $startTime = $log['data']['time'];
                break;
            }
        }
        
        foreach ($logs as $log) {
            $icon = $this->getIconForLogType($log['type']);
            $html .= '<div class="ct-timeline-item">';
            $html .= '<div class="ct-timeline-title">' . $icon . ' ' . htmlspecialchars($log['message']) . '</div>';
            
            // Show relative time if available
            if ($startTime !== null && isset($log['data']['time'])) {
                $relativeTime = round(($log['data']['time'] - $startTime) * 1000, 2);
                $html .= '<div class="ct-timeline-time">+' . $relativeTime . 'ms</div>';
            }
            
            if (!empty($log['data']) && is_array($log['data'])) {
                $html .= '<div class="ct-timeline-detail">';
                foreach ($log['data'] as $key => $value) {
                    if (in_array($key, ['time', 'timestamp'])) continue;
                    if (is_array($value)) {
                        if ($key === 'files' && count($value) > 0) {
                            $html .= '<div>' . count($value) . ' files</div>';
                        } elseif ($key === 'variables' && count($value) > 0) {
                            $html .= '<div>' . count($value) . ' variables</div>';
                        }
                        continue;
                    }
                    // Skip null values or convert to string
                    if ($value === null) {
                        $html .= '<div><strong>' . htmlspecialchars($key) . ':</strong> <span style="color: #999;">null</span></div>';
                    } else {
                        $html .= '<div><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars((string)$value) . '</div>';
                    }
                }
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Get icon for log type
     */
    private function getIconForLogType(string $type): string
    {
        $icons = [
            'init' => '⚙️',
            'template_start' => '📄',
            'template_end' => '✅',
            'layout' => '📐',
            'import' => '📥',
            'css_compile' => '🎨',
            'js_compile' => '⚡',
            'variable' => '🏷️',
            'base64' => '🖼️',
            'asset_copy' => '📋',
        ];
        
        return $icons[$type] ?? '•';
    }

    /**
     * Render JavaScript for tab switching
     */
    private function renderScripts(): string
    {
        return <<<'HTML'
        <script>
            (function() {
                const tabs = document.querySelectorAll('.ct-tab');
                const panes = document.querySelectorAll('.ct-tab-pane');
                
                tabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        const targetPane = this.getAttribute('data-tab');
                        
                        // Update tabs
                        tabs.forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Update panes
                        panes.forEach(p => {
                            if (p.getAttribute('data-pane') === targetPane) {
                                p.classList.add('active');
                            } else {
                                p.classList.remove('active');
                            }
                        });
                    });
                });
            })();
        </script>
HTML;
    }
}
