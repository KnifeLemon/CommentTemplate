<?php
umask(0002); // Allow group write permissions on created files and folders

$ds = DIRECTORY_SEPARATOR;
$TOP_PATH = __DIR__ . $ds . '..' . $ds . '..';

/**
 * load composer's autoloader
 */
$autoloadPath = $TOP_PATH . $ds . 'vendor' . $ds . 'autoload.php';
if (file_exists($autoloadPath)) {
    require($autoloadPath);
}

/**
 * If configuration file is missing, halt with an error.
 */
if(file_exists(__DIR__. $ds . 'config.php') === false) {
	Flight::halt(500, 'Configuration file not found.');
}

$app = Flight::app(); 

/**
 * Load configuration file
 */
$config = require('config.php');
$app->set('config', $config);


/**
 * Load service configuration file
 */
require('services.php');

/**
 * load routes configuration file
 */
require('routes.php');

/**
 * start the app
 */
$app->start();