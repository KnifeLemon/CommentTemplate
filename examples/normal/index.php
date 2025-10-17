<?php
require_once '../../vendor/autoload.php';

use CommentTemplate\CommentTemplate;

// Initialize template engine
$template = new CommentTemplate(__DIR__ . '/templates', '.php');
$template->setPublicPath(__DIR__ . '/public');

// Sample data
$data = [
    'title' => 'CommentTemplate Example',
    'description' => 'This is a demonstration of the CommentTemplate engine.',
    'user' => [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ],
    'items' => [
        'PHP Template Engine',
        'Asset Compilation',
        'Variable Processing',
        'Template Inheritance'
    ],
    'isLoggedIn' => true,
    'content' => "This is some content\nwith multiple lines\nto demonstrate nl2br filter."
];

// Render the template
echo $template->fetch('homepage', $data);