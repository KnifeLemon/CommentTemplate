<?php

namespace KnifeLemon\CommentTemplate;

/**
 * VariableFunction class provides utility functions for template variables.
 * These functions can be used in templates to manipulate variable values.
 */
class VariableFunction
{
    /**
     * Check if a function exists in this class
     *
     * @param string $function Function name to check
     * @return bool True if function exists
     */
    public static function hasFunction(string $function): bool
    {
        return method_exists(__CLASS__, $function);
    }

    /**
     * Convert string to lowercase
     *
     * @param string $value Input value
     * @return string Lowercase string
     */
    public static function lower(string $value): string
    {
        return strtolower($value);
    }

    /**
     * Convert string to uppercase
     *
     * @param string $value Input value
     * @return string Uppercase string
     */
    public static function upper(string $value): string
    {
        return strtoupper($value);
    }

    /**
     * Strip HTML tags from string
     *
     * @param string $value Input value
     * @return string String without HTML tags
     */
    public static function striptag(string $value): string
    {
        return strip_tags($value);
    }

    /**
     * Convert newlines to HTML line breaks
     *
     * @param string $value Input value
     * @return string String with HTML line breaks
     */
    public static function nl2br(string $value): string
    {
        return nl2br($value);
    }

    /**
     * Convert HTML line breaks to newlines
     *
     * @param string $value Input value
     * @return string String with newlines
     */
    public static function br2nl(string $value): string
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $value);
    }

    /**
     * Escape HTML special characters
     *
     * @param string $value Input value
     * @return string Escaped string
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value);
    }

    /**
     * Trim whitespace from string
     *
     * @param string $value Input value
     * @return string Trimmed string
     */
    public static function trim(string $value): string
    {
        return trim($value);
    }

    /**
     * Convert string to title case
     *
     * @param string $value Input value
     * @return string Title case string
     */
    public static function title(string $value): string
    {
        return ucwords($value);
    }
}