<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Flash\Messages;
use Carbon\Carbon;
use Hexlet\Code\Urls\UrlRepository;
use Hexlet\Code\Urls\Url;
use Hexlet\Code\Validator;
use Hexlet\Code\UrlChecks\UrlCheckRepository;
use Hexlet\Code\UrlChecks\UrlCheck;

$autoloadPath1 = __DIR__ . '/../../../autoload.php';
$autoloadPath2 = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath1)) {
    require_once $autoloadPath1;
} else {
    require_once $autoloadPath2;
}

session_start();

$container = new Container();
$container->set('renderer', function () {
    $renderer = new Slim\Views\PhpRenderer(__DIR__ . '/../templates');
    $renderer->setLayout('layout.phtml');

    return $renderer;
});

$container->set('flash', function () {
    return new Messages();
});

$container->set(PDO::class, function () {
    $databaseUrl = parse_url(getenv('DATABASE_URL'));

    $username = $databaseUrl['user'];
    $password = $databaseUrl['pass'];
    $host = $databaseUrl['host'];
    $port = $databaseUrl['port'] ?? 5432;
    $dbName = ltrim($databaseUrl['path'], '/');

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
});

$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$repo = $container->get(UrlRepository::class);
$checkRepo = $container->get(UrlCheckRepository::class);

$app->get('/', function (Request $request, Response $response) {
    $params = [
        'flash' => [],
        'errors' => [],
        'url' => ''
    ];

    return $this->get('renderer')->render($response, 'index.phtml', $params);
});

$app->post('/', function ($request, $response) use ($router, $repo) {
    $body = $request->getParsedBody();
    $url = $body['url'] ?? null;
    $urlData = ['url' => $url];
    $validator = new Validator();
    $errors = $validator->validate($urlData);

    if (count($errors) > 0) {
        $params = [
            'url' => $url,
            'errors' => $errors
        ];
        $response = $response->withStatus(422);

        return $this->get('renderer')->render($response, 'index.phtml', $params);
    }

    $parsedUrl = parse_url($url);
    $name = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
    $duplicateUrl = $repo->findByName($name);

    if ($duplicateUrl !== null) {
        $errors['duplicate'] = ['Страница уже существует'];
        $params = [
            'url' => $url,
            'errors' => $errors
        ];
        $response = $response->withStatus(422);

        return $this->get('renderer')->render($response, 'index.phtml', $params);
    }

    $urlObject = new Url();
    $urlObject->setName($name);
    $urlObject->setCreatedAt(Carbon::now());

    $repo->save($urlObject);

    $this->get('flash')->addMessage(
        'success',
        'Страница успешно добавлена'
    );

    $urlPath = $router->urlFor('url', ['id' => $urlObject->getId()]);

    return $response->withHeader('Location', $urlPath)->withStatus(302);
});

$app->get('/urls', function ($request, $response) use ($repo) {
    $flash = $this->get('flash')->getMessages();
    $urlsWithCheck = $repo->getAllWithLastCheck();

    $params = [
        'urlsWithCheck' => $urlsWithCheck,
        'flash' => $flash
    ];

    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls');

$app->get('/urls/{id}', function ($request, $response, $args) use ($repo, $checkRepo) {
    $id = (int) $args['id'];
    $urlObject = $repo->find($id);
    if ($urlObject === null) {
        $response->getBody()->write('Сайт не найден');
        return $response->withStatus(404);
    }

    $flash = $this->get('flash')->getMessages();
    $checks = $checkRepo->getByUrlId($id);

    $params = [
        'url' => $urlObject,
        'flash' => $flash,
        'checks' => $checks
    ];

    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('url');

$app->post('/urls/{id}/checks', function ($request, $response, $args) use ($repo, $checkRepo, $router) {
    $urlId = (int) $args['id'];

    $url = $repo->find($urlId);
    if ($url === null) {
        $this->get('flash')->addMessage(
            'error',
            'Произошла ошибка при проверке, не удалось подключиться'
        );

        $urlPath = $router->urlFor('url', ['id' => $urlId]);

        return $response->withHeader('Location', $urlPath)->withStatus(302);
    }

    $check = new UrlCheck();
    $check->setUrlId($urlId);
    $check->setCreatedAt(Carbon::now());

    $checkRepo->create($check);

    $this->get('flash')->addMessage('success', 'Страница успешно проверена');

    $urlPath = $router->urlFor('url', ['id' => $urlId]);

    return $response->withHeader('Location', $urlPath)->withStatus(302);
});

$app->run();
