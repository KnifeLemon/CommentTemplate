<?php

use app\controllers; // import 

$router = $app->router();

$router->group('/', function () use ($router, $app) {
	$index_controller = new controllers\IndexController();

	$router->get('', [ $index_controller, 'index' ], false, 'index');
	$router->get('(index)', [ $index_controller, 'index' ], false, 'index_has');

	$router->get('redirect', [ $index_controller, 'redirect' ], false, 'redirect');
});