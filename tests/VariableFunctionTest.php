<?php

namespace KnifeLemon\CommentTemplate\Tests;

use PHPUnit\Framework\TestCase;
use KnifeLemon\CommentTemplate\VariableFunction;

class VariableFunctionTest extends TestCase
{
    public function testHasFunction()
    {
        $this->assertTrue(VariableFunction::hasFunction('upper'));
        $this->assertTrue(VariableFunction::hasFunction('lower'));
        $this->assertFalse(VariableFunction::hasFunction('nonexistent'));
    }

    public function testUpperFunction()
    {
        $result = VariableFunction::upper('hello world');
        $this->assertEquals('HELLO WORLD', $result);
    }

    public function testLowerFunction()
    {
        $result = VariableFunction::lower('HELLO WORLD');
        $this->assertEquals('hello world', $result);
    }

    public function testStriptagFunction()
    {
        $result = VariableFunction::striptag('<p>Hello <strong>World</strong></p>');
        $this->assertEquals('Hello World', $result);
    }

    public function testNl2brFunction()
    {
        $result = VariableFunction::nl2br("Line 1\nLine 2");
        $this->assertStringContainsString('<br', $result);
    }

    public function testBr2nlFunction()
    {
        $result = VariableFunction::br2nl('Line 1<br>Line 2<br/>Line 3');
        $this->assertEquals("Line 1\nLine 2\nLine 3", $result);
    }

    public function testEscapeFunction()
    {
        $result = VariableFunction::escape('<script>alert("test")</script>');
        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringContainsString('&quot;test&quot;', $result);
    }

    public function testTrimFunction()
    {
        $result = VariableFunction::trim('  hello world  ');
        $this->assertEquals('hello world', $result);
    }

    public function testTitleFunction()
    {
        $result = VariableFunction::title('hello world');
        $this->assertEquals('Hello World', $result);
    }
}