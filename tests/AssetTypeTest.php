<?php

namespace KnifeLemon\CommentTemplate\Tests;

use PHPUnit\Framework\TestCase;
use KnifeLemon\CommentTemplate\AssetType;

class AssetTypeTest extends TestCase
{
    public function testGetExtension()
    {
        $this->assertEquals('css', AssetType::getExtension(AssetType::CSS));
        $this->assertEquals('css', AssetType::getExtension(AssetType::CSS_SINGLE));
        $this->assertEquals('js', AssetType::getExtension(AssetType::JS));
        $this->assertEquals('js', AssetType::getExtension(AssetType::JS_ASYNC));
        $this->assertEquals('js', AssetType::getExtension(AssetType::JS_DEFER));
        $this->assertEquals('js', AssetType::getExtension(AssetType::JS_TOP));
        $this->assertEquals('js', AssetType::getExtension(AssetType::JS_TOP_ASYNC));
        $this->assertEquals('js', AssetType::getExtension(AssetType::JS_TOP_DEFER));
        $this->assertEquals('js', AssetType::getExtension(AssetType::JS_SINGLE));
        $this->assertEquals('js', AssetType::getExtension(AssetType::JS_SINGLE_ASYNC));
        $this->assertEquals('js', AssetType::getExtension(AssetType::JS_SINGLE_DEFER));
        $this->assertEquals('', AssetType::getExtension('invalid'));
    }

    public function testIsSingleType()
    {
        $this->assertTrue(AssetType::isSingleType(AssetType::CSS_SINGLE));
        $this->assertTrue(AssetType::isSingleType(AssetType::JS_SINGLE));
        $this->assertTrue(AssetType::isSingleType(AssetType::JS_SINGLE_ASYNC));
        $this->assertTrue(AssetType::isSingleType(AssetType::JS_SINGLE_DEFER));
        $this->assertFalse(AssetType::isSingleType(AssetType::CSS));
        $this->assertFalse(AssetType::isSingleType(AssetType::JS));
        $this->assertFalse(AssetType::isSingleType(AssetType::JS_ASYNC));
        $this->assertFalse(AssetType::isSingleType(AssetType::JS_DEFER));
        $this->assertFalse(AssetType::isSingleType(AssetType::JS_TOP));
        $this->assertFalse(AssetType::isSingleType(AssetType::JS_TOP_ASYNC));
        $this->assertFalse(AssetType::isSingleType(AssetType::JS_TOP_DEFER));
    }
}