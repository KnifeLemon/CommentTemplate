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
            "description" => "This is a   TEST   with spaces",
            "html_content" => "<script>alert('XSS')</script>Hello World",
        ]);
    }
    
    public function redirect()
    {
        $this->app->redirect($this->app->getUrl('index'));
    }

    protected function getDefaultData(): array
    {
        return [
            'title' => 'FlightPHP Example Site',
        ];
    }
}