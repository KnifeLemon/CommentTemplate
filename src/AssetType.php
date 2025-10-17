<?php

namespace KnifeLemon\CommentTemplate;

/**
 * AssetType class defines constants for different asset types
 * and provides utility methods for asset handling.
 */
class AssetType
{
    const CSS = 'css';
    const CSS_SINGLE = 'cssSingle';
    const JS = 'js';
    const JS_ASYNC = 'jsAsync';
    const JS_DEFER = 'jsDefer';
    const JS_TOP = 'jsTop';
    const JS_TOP_ASYNC = 'jsTopAsync';
    const JS_TOP_DEFER = 'jsTopDefer';
    const JS_SINGLE = 'jsSingle';
    const JS_SINGLE_ASYNC = 'jsSingleAsync';
    const JS_SINGLE_DEFER = 'jsSingleDefer';

    /**
     * Get file extension for the given asset type
     *
     * @param string $type Asset type constant
     * @return string File extension
     */
    public static function getExtension(string $type): string
    {
        switch ($type) {
            case self::CSS:
            case self::CSS_SINGLE:
                return 'css';
            case self::JS:
            case self::JS_TOP:
            case self::JS_SINGLE:
            case self::JS_ASYNC:
            case self::JS_TOP_ASYNC:
            case self::JS_SINGLE_ASYNC:
            case self::JS_DEFER:
            case self::JS_TOP_DEFER:
            case self::JS_SINGLE_DEFER:
                return 'js';
            default:
                return '';
        }
    }

    /**
     * Check if the given type is a single asset type
     *
     * @param string $type Asset type constant
     * @return bool True if it's a single asset type
     */
    public static function isSingleType(string $type): bool
    {
        return in_array($type, [self::CSS_SINGLE, self::JS_SINGLE, self::JS_SINGLE_ASYNC, self::JS_SINGLE_DEFER]);
    }

    /**
     * Get all available asset types
     *
     * @return array Array of asset type constants
     */
    public static function getAllTypes(): array
    {
        return [
            self::CSS,
            self::CSS_SINGLE,
            self::JS,
            self::JS_ASYNC,
            self::JS_DEFER,
            self::JS_TOP,
            self::JS_TOP_ASYNC,
            self::JS_TOP_DEFER,
            self::JS_SINGLE,
            self::JS_SINGLE_ASYNC,
            self::JS_SINGLE_DEFER
        ];
    }
}