<?php
require_once '../../vendor/autoload.php';

use KnifeLemon\CommentTemplate\Engine;

// Initialize template engine
$template = new Engine();
$template->setPublicPath(__DIR__);
$template->setAssetPath(__DIR__ . '/assets');
$template->setSkinPath(__DIR__ . '/templates');
$template->setFileExtension('.php');

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