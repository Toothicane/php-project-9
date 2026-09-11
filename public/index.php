<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Flash\Messages;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;
use Hexlet\Code\Urls\UrlRepository;
use Hexlet\Code\Urls\Url;
use Hexlet\Code\Validator;
use Hexlet\Code\UrlChecks\UrlCheckRepository;
use Hexlet\Code\UrlChecks\UrlCheck;

const REQUEST_TIMEOUT = 10;

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
        $this->get('flash')->addMessage(
            'error',
            'Страница уже существует'
        );
    
        $urlPath = $router->urlFor('url', ['id' => $duplicateUrl->getId()]);
    
        return $response->withHeader('Location', $urlPath)->withStatus(302);
    }

    $urlObject = new Url();
    $urlObject->setName($name);
    $urlObject->setCreatedAt(Carbon::now());

    $repo->create($urlObject);

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

    $client = new Client([
        'http_errors' => false,
        'timeout' => REQUEST_TIMEOUT
    ]);

    try {
        $responseFromSite = $client->request('GET', $url->getName());

        $statusCode = $responseFromSite->getStatusCode();
        $html = $responseFromSite->getBody()->getContents();

        $crawler = new Crawler($html);
        $h1 = null;
        if ($crawler->filter('h1')->count() > 0) {
            $h1 = $crawler->filter('h1')->first()->text();
        }
        $title = null;
        if ($crawler->filter('title')->count() > 0) {
            $title = $crawler->filter('title')->first()->text();
        }
        $description = null;
        if ($crawler->filter('meta[name="description"]')->count() > 0) {
            $description = $crawler->filter('meta[name="description"]')->first()->attr('content');
        }

        $check = new UrlCheck();
        $check->setUrlId($urlId);
        $check->setStatusCode($statusCode);
        $check->setH1($h1);
        $check->setTitle($title);
        $check->setDescription($description);
        $check->setCreatedAt(Carbon::now());

        $checkRepo->create($check);

        $this->get('flash')->addMessage(
            'success',
            'Страница успешно проверена'
        );
    } catch (GuzzleException) {
        $this->get('flash')->addMessage(
            'error',
            'Произошла ошибка при проверке, не удалось подключиться'
        );
    }

    $urlPath = $router->urlFor('url', ['id' => $urlId]);

    return $response->withHeader('Location', $urlPath)->withStatus(302);
});

$app->run();
