[![Latest Stable Version](https://poser.pugx.org/knifelemon/comment-template/v/stable)](https://packagist.org/packages/knifelemon/comment-template)
[![Total Downloads](https://poser.pugx.org/knifelemon/comment-template/downloads)](https://packagist.org/packages/knifelemon/comment-template)
[![Latest Unstable Version](https://poser.pugx.org/knifelemon/comment-template/v/unstable)](https://packagist.org/packages/knifelemon/comment-template)
[![License](https://poser.pugx.org/knifelemon/comment-template/license)](https://packagist.org/packages/knifelemon/comment-template)
[![PHP Version Require](https://poser.pugx.org/knifelemon/comment-template/require/php)](https://packagist.org/packages/knifelemon/comment-template)

# CommentTemplate

A powerful PHP template engine with asset compilation, template inheritance, and variable processing. CommentTemplate provides a simple yet flexible way to manage templates with built-in CSS/JS minification and caching.

## Features

- **Template Inheritance**: Use layouts and include other templates
- **Asset Compilation**: Automatic CSS/JS minification and caching
- **Variable Processing**: Template variables with filters and commands
- **Base64 Encoding**: Inline assets as data URIs
- **Flight Framework Integration**: Optional integration with Flight PHP framework

## Installation

Install via Composer:

```bash
composer require knifelemon/comment-template
```

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use KnifeLemon\CommentTemplate\Engine;

// Initialize template engine
$template = new Engine('/path/to/templates', '.php');
$template->setPublicPath('/path/to/public');

// Render template
$template->render('homepage', [
    'title' => 'Welcome',
    'content' => 'Hello World!'
]);
```

## Template Directives

### Asset Loading Strategies

CommentTemplate supports different JavaScript loading strategies:

- **Normal**: `<!--@js(file)-->` - Standard blocking script load
- **Async**: `<!--@jsAsync(file)-->` - Non-blocking, executes immediately when loaded
- **Defer**: `<!--@jsDefer(file)-->` - Non-blocking, waits for HTML parsing to complete
- **Top placement**: Use `jsTop*` variants to load scripts in the `<head>` section
- **Single files**: Use `*Single*` variants to skip minification and load individual files

### Layout Inheritance

Use layouts to create a common structure:

**layout.php**:
```html
<!DOCTYPE html>
<html>
<head>
    <title>{$title}</title>
</head>
<body>
    <!--@contents-->
</body>
</html>
```

**page.php**:
```html
<!--@layout(layout)-->
<h1>{$title}</h1>
<p>{$content}</p>
```

### Asset Management

#### CSS Files
```html
<!--@css(/css/styles.css)-->          <!-- Minified and cached -->
<!--@cssSingle(/css/critical.css)-->  <!-- Single file, not minified -->
```

#### JavaScript Files
```html
<!--@js(/js/script.js)-->             <!-- Minified, loaded at bottom -->
<!--@jsAsync(/js/analytics.js)-->     <!-- Minified, loaded at bottom with async -->
<!--@jsDefer(/js/utils.js)-->         <!-- Minified, loaded at bottom with defer -->
<!--@jsTop(/js/critical.js)-->        <!-- Minified, loaded in head -->
<!--@jsTopAsync(/js/tracking.js)-->   <!-- Minified, loaded in head with async -->
<!--@jsTopDefer(/js/polyfill.js)-->   <!-- Minified, loaded in head with defer -->
<!--@jsSingle(/js/widget.js)-->       <!-- Single file, not minified -->
<!--@jsSingleAsync(/js/ads.js)-->     <!-- Single file, not minified, async -->
<!--@jsSingleDefer(/js/social.js)-->  <!-- Single file, not minified, defer -->
```

#### Base64 Encoding
```html
<!--@base64(images/logo.png)-->       <!-- Inline as data URI -->
```

#### Asset Copying
```html
<!--@asset(images/photo.jpg)-->       <!-- Copy single asset to public directory -->
<!--@assetDir(assets)-->              <!-- Copy entire directory to public directory -->
```

#### Asset Directives in CSS/JS Files

CommentTemplate also processes asset directives within CSS and JavaScript files during compilation:

**CSS Example:**
```css
/* In your CSS files */
@font-face {
    font-family: 'CustomFont';
    src: url('<!--@asset(fonts/custom.woff2)-->') format('woff2');
}

.background-image {
    background: url('<!--@asset(images/bg.jpg)-->');
}

.inline-icon {
    background: url('<!--@base64(icons/star.svg)-->');
}
```

**JavaScript Example:**
```javascript
/* In your JS files */
const fontUrl = '<!--@asset(fonts/custom.woff2)-->';
const imageData = '<!--@base64(images/icon.png)-->';
```

**Benefits:**
- Asset directives are processed during CSS/JS compilation
- Files are automatically copied to the public directory
- URLs are generated with correct asset paths
- Base64 encoding works in CSS/JS files too

### Asset Path Configuration

You can configure where assets are stored using either relative or absolute paths:

```php
$template = new CommentTemplate('/path/to/templates', '.php');
$template->setPublicPath('/path/to/public');

// Relative to public path (default behavior)
$template->setAssetPath('assets');        // → /path/to/public/assets/
$template->setAssetPath('static/files');  // → /path/to/public/static/files/

// Absolute paths
$template->setAssetPath('/var/www/cdn');           // → /var/www/cdn/
$template->setAssetPath('/full/path/to/assets');   // → /full/path/to/assets/

// FlightPHP integration with absolute path
Flight::set('flight.views.assetPath', Flight::get('flight.views.topPath') . '/assets');
```

**How it works:**
- `@css` and `@js` directives create minified files in: `{publicPath}/{assetPath}/css/` or `{publicPath}/{assetPath}/js/`
- `@asset` directive copies single files to: `{publicPath}/{assetPath}/{relativePath}`
- `@assetDir` directive copies entire directories to: `{publicPath}/{assetPath}/{relativePath}`
- Files are only copied when source is newer than destination
- URLs are generated with the configured asset path

#### Asset Directory Copying Examples

```html
<!-- Copy entire assets folder -->
<!--@assetDir(assets)-->

<!-- Copy specific subdirectory -->
<!--@assetDir(images)-->
<!--@assetDir(fonts)-->
```

**Directory structure example:**
```
templates/
├── assets/
│   ├── images/
│   │   ├── logo.png
│   │   └── banner.jpg
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
└── layout.php

After <!--@assetDir(assets)--> in template:

public/
└── assets/           # (configured asset path)
    └── assets/       # (copied directory)
        ├── images/
        ├── css/
        └── js/
```

### Template Includes
```html
<!--@import(components/header)-->     <!-- Include other templates -->
```

### Variable Processing

#### Basic Variables
```html
<h1>{$title}</h1>
<p>{$description}</p>
```

#### Variable Filters
```html
{$title|upper}                       <!-- Convert to uppercase -->
{$content|lower}                     <!-- Convert to lowercase -->
{$html|striptag}                     <!-- Strip HTML tags -->
{$text|escape}                       <!-- Escape HTML -->
{$multiline|nl2br}                   <!-- Convert newlines to <br> -->
{$description|trim}                  <!-- Trim whitespace -->
```

#### Variable Commands
```html
{$title|default=Default Title}       <!-- Set default value -->
{$name|concat= (Admin)}              <!-- Concatenate text -->
```

#### Chain Multiple Filters
```html
{$content|striptag|trim|escape}      <!-- Chain multiple filters -->
```

### Comments

Template comments are completely removed from the output and won't appear in the final HTML:

```html
{* This is a single-line template comment *}

{* 
   This is a multi-line 
   template comment 
   that spans several lines
*}

<h1>{$title}</h1>
{* Debug comment: checking if title variable works *}
<p>{$content}</p>
```

**Note**: Template comments `{* ... *}` are different from HTML comments `<!-- ... -->`. Template comments are removed during processing and never reach the browser.

## API Reference

### Engine Class

#### Constructor
```php
public function __construct(string $publicPath = "", string $skinPath = "", string $assetPath = "", string $fileExtension = "")
```

#### Methods

**render(string $template, array $data = []): void**
- Render template and output to browser

**fetch(string $template, array $data = []): string**
- Render template and return as string

**setPublicPath(string $path): void**
- Set public path for asset compilation

**setSkinPath(string $path): void**
- Set template directory path

**setFileExtension(string $extension): void**
- Set template file extension

**setAssetPath(string $path): void**
- Set asset storage path relative to public directory

**getPublicPath(): string**
- Get current public path

**getSkinPath(): string**
- Get current template directory path

**getFileExtension(): string**
- Get current template file extension

**getAssetPath(): string**
- Get current asset storage path

## Examples

### Basic Usage

```php
$template = new Engine('./templates', '.html');
$template->setPublicPath('./public');

$template->render('welcome', [
    'title' => 'Welcome to My Site',
    'user' => 'John Doe',
    'isLoggedIn' => true
]);
```

### With Flight Framework

```php
// Flight will automatically configure paths
$template = new Engine();

Flight::route('/', function() use ($template) {
    $template->render('homepage', [
        'title' => 'Home Page'
    ]);
});
```

### Asset Compilation Example

**template.php**:
```html
<!--@layout(layout)-->
<!--@css(/css/bootstrap.css)-->
<!--@css(/css/custom.css)-->
<!--@js(/js/jquery.js)-->
<!--@js(/js/app.js)-->

<div class="container">
    <h1>{$title|escape}</h1>
    <p>{$content|nl2br}</p>
</div>
```

This will:
1. Minify and combine CSS files
2. Minify and combine JS files
3. Cache compiled assets
4. Inject `<link>` and `<script>` tags automatically

## File Structure

```
your-project/
├── templates/
│   ├── layout.php
│   ├── homepage.php
│   └── components/
│       └── header.php
├── public/
│   ├── css/
│   ├── js/
│   └── assets/          <!-- Generated cache directory -->
│       ├── css/
│       └── js/
└── vendor/
    └── commenttemplate/
```

## Development

### Running Tests

```bash
composer test
```

### Code Analysis

```bash
composer phpstan
```

### Test Coverage

```bash
composer test-coverage
```

## License

MIT License. See LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## Support

- [GitHub Issues](https://github.com/KnifeLemon/CommentTemplate/issues)