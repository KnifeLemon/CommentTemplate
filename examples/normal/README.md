# CommentTemplate Examples

This directory contains example usage of the CommentTemplate engine.

## Files Structure

```
examples/
├── index.php                 # Main example file
├── templates/                # Template files
│   ├── layout.php           # Base layout
│   ├── homepage.php         # Homepage template
│   └── components/          # Reusable components
│       ├── header.php
│       ├── footer.php
│       └── sidebar.php
├── public/                  # Public assets
│   ├── css/
│   │   └── custom.css
│   └── js/
│       └── app.js
└── README.md               # This file
```

## Running the Example

1. Make sure you have installed CommentTemplate via Composer:
   ```bash
   composer install
   ```

2. Start a local PHP server:
   ```bash
   cd examples
   php -S localhost:8000
   ```

3. Open your browser and navigate to `http://localhost:8000`

## What the Example Demonstrates

- **Template Inheritance**: `homepage.php` extends `layout.php`
- **Component Inclusion**: Header, footer, and sidebar components
- **Asset Compilation**: CSS and JS files are minified and cached
- **Variable Processing**: Various filters and commands
- **Base64 Encoding**: Logo image (if available)

## Template Features Shown

### Variable Filters
- `{$title|escape}` - HTML escaping
- `{$content|nl2br|escape}` - Newlines to breaks + escaping
- `{$user.name|escape|default=Guest}` - Default values

### Asset Management
- CSS minification and combination
- JavaScript minification and combination
- Automatic injection into HTML

### Template Structure
- Layout inheritance with `<!--@layout(layout)-->`
- Content placeholder with `<!--@contents-->`
- Component imports with `<!--@import(components/header)-->`

This example provides a complete demonstration of CommentTemplate's capabilities in a real-world scenario.