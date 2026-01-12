<?php

namespace KnifeLemon\CommentTemplate;

/**
 * EchoProcessor handles the processing of @echo directives in templates and assets.
 */
class EchoProcessor
{
    /**
     * Process @echo directives by executing PHP code
     *
     * @param string $content Content to process (passed by reference)
     * @param array $data Template data for variable scope
     * @return void
     */
    public static function process(string &$content, array $data): void
    {
        // Extract template data to make variables available in eval scope
        extract($data);
        
        // Manual parsing to handle nested parentheses and string literals
        $pos = 0;
        while (($start = strpos($content, '<!--@echo(', $pos)) !== false) {
            $start += 10; // Length of '<!--@echo('
            $depth = 1;
            $i = $start;
            $end = null;
            $inString = false;
            $stringChar = null;
            $escaped = false;
            
            // Find matching closing parenthesis, respecting string literals
            while ($i < strlen($content) && $depth > 0) {
                $char = $content[$i];
                
                // Handle escape sequences
                if ($escaped) {
                    $escaped = false;
                    $i++;
                    continue;
                }
                
                if ($char === '\\') {
                    $escaped = true;
                    $i++;
                    continue;
                }
                
                // Track string boundaries
                if (($char === '"' || $char === "'") && !$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar) {
                    $inString = false;
                    $stringChar = null;
                }
                
                // Only count parentheses outside of strings
                if (!$inString) {
                    if ($char === '(') {
                        $depth++;
                    } elseif ($char === ')') {
                        $depth--;
                        if ($depth === 0) {
                            $end = $i;
                            break;
                        }
                    }
                }
                
                $i++;
            }
            
            if ($end !== null && substr($content, $end + 1, 3) === '-->') {
                $code = substr($content, $start, $end - $start);
                $fullMatch = '<!--@echo(' . $code . ')-->';
                
                try {
                    // Execute PHP code and capture output
                    ob_start();
                    $result = eval('return ' . $code . ';');
                    $output = ob_get_clean();
                    
                    // Use eval result or output buffer content
                    $value = $output !== '' ? $output : (string)$result;
                    
                    $content = substr_replace($content, $value, $start - 10, strlen($fullMatch));
                    $pos = $start - 10 + strlen($value);
                } catch (\Throwable $e) {
                    // Replace with empty string on error
                    $content = substr_replace($content, '', $start - 10, strlen($fullMatch));
                    $pos = $start - 10;
                }
            } else {
                // Malformed directive, skip
                $pos = $start;
            }
        }
    }
}
