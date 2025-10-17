<?php

namespace KnifeLemon\CommentTemplate\Tests;

use PHPUnit\Framework\TestCase;
use KnifeLemon\CommentTemplate\Engine;

class CommentTemplateTest extends TestCase
{
    private $templateEngine;
    private $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'comment_template_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'templates');
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'public');

        $this->templateEngine = new Engine(
            '', // publicPath
            $this->tempDir . DIRECTORY_SEPARATOR . 'templates', // skinPath
            '', // assetPath
            '.php' // fileExtension
        );
        $this->templateEngine->setPublicPath($this->tempDir . DIRECTORY_SEPARATOR . 'public');
        $this->templateEngine->setAssetPath('assets'); // Set default asset path for tests
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testBasicTemplateRendering()
    {
        $templateContent = '<h1>{$title}</h1><p>{$content}</p>';
        file_put_contents($this->tempDir . '/templates/test.php', $templateContent);

        $result = $this->templateEngine->fetch('test', [
            'title' => 'Test Title',
            'content' => 'Test Content'
        ]);

        $this->assertStringContainsString('<h1>Test Title</h1>', $result);
        $this->assertStringContainsString('<p>Test Content</p>', $result);
    }

    public function testVariableFilters()
    {
        $templateContent = '{$text|upper} - {$text|lower} - {$html|escape}';
        file_put_contents($this->tempDir . '/templates/filters.php', $templateContent);

        $result = $this->templateEngine->fetch('filters', [
            'text' => 'Hello World',
            'html' => '<script>alert("test")</script>'
        ]);

        $this->assertStringContainsString('HELLO WORLD', $result);
        $this->assertStringContainsString('hello world', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testLayoutInheritance()
    {
        $layoutContent = '<html><head><title><?= $title ?></title></head><body><!--@contents--></body></html>';
        $pageContent = '<!--@layout(layout)--><h1>Page Content</h1>';

        file_put_contents($this->tempDir . '/templates/layout.php', $layoutContent);
        file_put_contents($this->tempDir . '/templates/page.php', $pageContent);

        $result = $this->templateEngine->fetch('page', ['title' => 'Test Page']);

        $this->assertStringContainsString('<title>Test Page</title>', $result);
        $this->assertStringContainsString('<h1>Page Content</h1>', $result);
        $this->assertStringContainsString('<html>', $result);
    }

    public function testTemplateImport()
    {
        $headerContent = '<header>Site Header</header>';
        $pageContent = '<!--@import(header)--><main>Main Content</main>';

        file_put_contents($this->tempDir . '/templates/header.php', $headerContent);
        file_put_contents($this->tempDir . '/templates/import_test.php', $pageContent);

        $result = $this->templateEngine->fetch('import_test', []);

        $this->assertStringContainsString('<header>Site Header</header>', $result);
        $this->assertStringContainsString('<main>Main Content</main>', $result);
    }

    public function testCommentRemoval()
    {
        $templateContent = "{* This is a comment *}\n<p>Visible content</p>\n{* Another comment *}";
        file_put_contents($this->tempDir . '/templates/comments.php', $templateContent);

        $result = $this->templateEngine->fetch('comments', []);

        $this->assertStringNotContainsString('{*', $result);
        $this->assertStringNotContainsString('*}', $result);
        $this->assertStringContainsString('<p>Visible content</p>', $result);
    }

    public function testVariableDefaults()
    {
        $templateContent = '{$missing|default=Default Value} - {$existing|default=Should Not Show}';
        file_put_contents($this->tempDir . '/templates/defaults.php', $templateContent);

        $result = $this->templateEngine->fetch('defaults', [
            'existing' => 'Existing Value'
        ]);

        $this->assertStringContainsString('Default Value', $result);
        $this->assertStringContainsString('Existing Value', $result);
        $this->assertStringNotContainsString('Should Not Show', $result);
    }

    public function testAsyncJavaScriptDirectives()
    {
        // Create a dummy JS file in the skin path (where templates look for assets)
        mkdir($this->tempDir . '/templates/js', 0755, true);
        file_put_contents($this->tempDir . '/templates/js/test.js', 'console.log("test");');

        $templateContent = '<!--@jsAsync(js/test.js)--><p>Content</p>';
        file_put_contents($this->tempDir . '/templates/async_test.php', $templateContent);

        $result = $this->templateEngine->fetch('async_test', []);

        $this->assertStringContainsString('<script src=', $result);
        $this->assertStringContainsString('async', $result);
        $this->assertStringContainsString('<p>Content</p>', $result);
    }

    public function testDeferJavaScriptDirectives()
    {
        // Create a dummy JS file in the skin path (where templates look for assets)
        mkdir($this->tempDir . '/templates/js', 0755, true);
        file_put_contents($this->tempDir . '/templates/js/deferred.js', 'console.log("deferred");');

        $templateContent = '<!--@jsDefer(js/deferred.js)--><p>Content</p>';
        file_put_contents($this->tempDir . '/templates/defer_test.php', $templateContent);

        $result = $this->templateEngine->fetch('defer_test', []);

        $this->assertStringContainsString('<script src=', $result);
        $this->assertStringContainsString('defer', $result);
        $this->assertStringContainsString('<p>Content</p>', $result);
    }

    public function testAssetPathConfiguration()
    {
        // Test default asset path
        $this->assertEquals('assets', $this->templateEngine->getAssetPath());

        // Test setting relative asset path
        $this->templateEngine->setAssetPath('static/files');
        $this->assertEquals('static/files', $this->templateEngine->getAssetPath());

        // Test setting absolute asset path
        $absolutePath = $this->tempDir . '/custom/assets';
        $this->templateEngine->setAssetPath($absolutePath);
        $this->assertEquals($absolutePath, $this->templateEngine->getAssetPath());

        // Test setting asset path with leading/trailing slashes
        $this->templateEngine->setAssetPath('/custom/path/');
        $this->assertEquals('/custom/path/', $this->templateEngine->getAssetPath());
    }

    public function testAssetDirectiveCopying()
    {
        // Create a test image file in templates directory
        mkdir($this->tempDir . '/templates/images', 0755, true);
        $testImagePath = $this->tempDir . '/templates/images/test.png';
        file_put_contents($testImagePath, 'fake-png-data');

        // Set custom asset path
        $this->templateEngine->setAssetPath('static');

        $templateContent = '<img src="<!--@asset(images/test.png)-->" alt="Test">';
        file_put_contents($this->tempDir . '/templates/asset_test.php', $templateContent);

        $result = $this->templateEngine->fetch('asset_test', []);

        // Should contain the asset path in the URL
        $this->assertStringContainsString('/static/images/test.png', $result);
        $this->assertStringContainsString('<img src="/static/images/test.png"', $result);

        // Check that file was copied to public directory
        $expectedPath = $this->tempDir . '/public/static/images/test.png';
        $this->assertFileExists($expectedPath);
        $this->assertEquals('fake-png-data', file_get_contents($expectedPath));
    }

    public function testAssetDirectorycopying()
    {
        // Create a test directory structure in templates
        mkdir($this->tempDir . '/templates/assets', 0755, true);
        mkdir($this->tempDir . '/templates/assets/images', 0755, true);
        mkdir($this->tempDir . '/templates/assets/css', 0755, true);
        
        // Add some test files
        file_put_contents($this->tempDir . '/templates/assets/images/logo.png', 'fake-png-data');
        file_put_contents($this->tempDir . '/templates/assets/images/icon.svg', '<svg>test</svg>');
        file_put_contents($this->tempDir . '/templates/assets/css/style.css', 'body { margin: 0; }');

        // Set custom asset path
        $this->templateEngine->setAssetPath('static');

        $templateContent = '<!--@assetDir(assets)--><p>Assets copied</p>';
        file_put_contents($this->tempDir . '/templates/asset_dir_test.php', $templateContent);

        $result = $this->templateEngine->fetch('asset_dir_test', []);

        // @assetDir should be removed from HTML (no output)
        $this->assertStringNotContainsString('<!--@assetDir', $result);
        $this->assertStringContainsString('<p>Assets copied</p>', $result);

        // Check that files were copied to public directory
        $this->assertFileExists($this->tempDir . '/public/static/assets/images/logo.png');
        $this->assertFileExists($this->tempDir . '/public/static/assets/images/icon.svg');
        $this->assertFileExists($this->tempDir . '/public/static/assets/css/style.css');
        
        $this->assertEquals('fake-png-data', file_get_contents($this->tempDir . '/public/static/assets/images/logo.png'));
        $this->assertEquals('<svg>test</svg>', file_get_contents($this->tempDir . '/public/static/assets/images/icon.svg'));
        $this->assertEquals('body { margin: 0; }', file_get_contents($this->tempDir . '/public/static/assets/css/style.css'));
    }

    public function testAssetDirectivesInCssFiles()
    {
        // Create test assets
        mkdir($this->tempDir . '/templates/assets', 0755, true);
        mkdir($this->tempDir . '/templates/assets/fonts', 0755, true);
        file_put_contents($this->tempDir . '/templates/assets/fonts/custom.woff2', 'fake-font-data');

        // Create CSS file with asset directives
        mkdir($this->tempDir . '/templates/css', 0755, true);
        $cssContent = '@font-face {
    font-family: "Custom";
    src: url("<!--@asset(assets/fonts/custom.woff2)-->") format("woff2");
}
.bg-image {
    background: url("<!--@base64(assets/fonts/custom.woff2)-->");
}';
        file_put_contents($this->tempDir . '/templates/css/fonts.css', $cssContent);

        $templateContent = '<!--@css(css/fonts.css)--><p>CSS with assets</p>';
        file_put_contents($this->tempDir . '/templates/css_asset_test.php', $templateContent);

        $result = $this->templateEngine->fetch('css_asset_test', []);

        // Should contain link tag for compiled CSS
        $this->assertStringContainsString('<link rel="stylesheet"', $result);
        
        // Check that asset file was copied
        $this->assertFileExists($this->tempDir . '/public/assets/assets/fonts/custom.woff2');
        $this->assertEquals('fake-font-data', file_get_contents($this->tempDir . '/public/assets/assets/fonts/custom.woff2'));
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}