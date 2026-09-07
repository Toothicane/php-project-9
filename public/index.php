<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    $renderer = new Slim\Views\PhpRenderer(__DIR__ . '/../templates');
    $renderer->setLayout('layout.phtml');

    return $renderer;
});

$app = AppFactory::createFromContainer($container);

$app->get('/', function (Request $request, Response $response) {
    $params = [
        'flash' => [],
    ];

    return $this->get('renderer')->render($response, 'index.phtml', $params);
});

$app->run();
