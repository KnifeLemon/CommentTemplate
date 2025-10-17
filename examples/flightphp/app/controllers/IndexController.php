<?php
namespace app\controllers;

use flight\Engine;

class IndexController {

    // Define base path for views
    CONST BASE_PATH = '/';

    protected Engine $app;

	public function __construct() {
		$this->app = \Flight::app();
	}
    public function index()
    {
        $this->app->render('main', [
            ...$this->getDefaultData(),
            "name" => "John Doe",
        ]);
    }
    
    public function redirect()
    {
        $this->app->redirect($this->app->getUrl('index'));
    }
    
    public function assetTest()
    {
        $this->app->render('asset_test', [
            ...$this->getDefaultData(),
            'title' => 'Asset Path Test',
        ]);
    }

    protected function getDefaultData(): array
    {
        return [
            'title' => 'FlightPHP Example Site',
        ];
    }
}