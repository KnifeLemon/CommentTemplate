<?php
// set timezone
date_default_timezone_set('Asia/Seoul');

// set error reporting level
error_reporting(E_ALL);

// set internal encoding
if(function_exists('mb_internal_encoding') === true) {
	mb_internal_encoding('UTF-8');
}

// set locale
if(function_exists('setlocale') === true) {
	setlocale(LC_ALL, 'ko_KR.UTF-8');
}

/* 
* flight settings
*/
$app->path($TOP_PATH);
$app->set('flight.base_url', '/'); // if this is in a subdirectory, you'll need to change this
$app->set('flight.case_sensitive', false); // if you want case sensitive routes, set this to true
$app->set('flight.log_errors', true); // if you want to log errors, set this to true
$app->set('flight.handle_errors', false); // if you want flight to handle errors, set this to true, otherwise Tracy will handle them
$app->set('flight.content_length', true); // if flight should send a content length header

$app->set('flight.views.topPath', $TOP_PATH); // top path
$app->set('flight.views.assetPath', $app->get('flight.views.topPath') . $ds . 'assets'); // asset path
$app->set('flight.views.path', $app->get('flight.views.topPath') . $ds . 'app' . $ds .'views'); // theme path ( after this, skin-1 etc. can also be used )
$app->set('flight.views.extension', '.phtml'); // template extension

return [
    'foo' => 'bar'
];