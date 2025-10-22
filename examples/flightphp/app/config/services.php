<?php

use KnifeLemon\CommentTemplate\Engine;

// Template Overried
$app->register('view', Engine::class, [], function (Engine $builder) use ($app) {
    $builder->setPublicPath($app->get('flight.views.topPath'));
    $builder->setAssetPath($app->get('flight.views.assetPath'));
    $builder->setSkinPath($app->get('flight.views.path'));
    $builder->setFileExtension($app->get('flight.views.extension'));
});
$app->map('render', function(string $template, array $data) use ($app): void {
    echo $app->view()->render($template, $data);
});