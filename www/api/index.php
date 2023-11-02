<?php

use DI\ContainerBuilder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/config/app-config.php';
require_once __DIR__ . '/data-access/db-access.php';
require_once __DIR__ . '/utils/utils.php';

require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/ImageRepository.php';

require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/ImageController.php';

// NOTE: the following setup is not super efficient, but gets the job done for now.

// Prepare DI container
$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

// Init repositories
$userRepo = UserRepository::getInstance($DB);
$imageRepo = ImageRepository::getInstance($DB, UPLOAD_DIR);

// Inject controllers
$container->set('userController', UserController::getInstance($userRepo));
$container->set('imageController', ImageController::getInstance($imageRepo, UPLOAD_DIR));

// Build app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add middleware
$app->addBodyParsingMiddleware();

$app->add(new \Tuupola\Middleware\JwtAuthentication([
    'secret' => JWT_SECRET,
    'attribute' => 'jwt',
    'algorithm' => ['HS256'],
    'path' => '/api',
    'ignore' => ['/api/register', '/api/login', '/api/images'],
    'secure' => false, // to allow unsecure (i.e. http) connection to local server for testing
    'error' => function ($response, $arguments) {
        $data['message'] = 'Unauthorized (JWT)';

        return json_response($response, $data, 401);
    }
]));

$app->add(new \Tuupola\Middleware\CorsMiddleware([
    'origin' => ['*'],
    'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'headers.allow' => ['Authorization', 'If-Match', 'If-Unmodified-Since'],
    'headers.expose' => [],
    'credentials' => false,
    'cache' => 0,
]));

// Define routes

// ------------------------------------------------------------------------------------------------
// User routes
// ------------------------------------------------------------------------------------------------

$app->post('/api/login', function (Request $request, Response $response, $args) {
    return $this->get('userController')->authenticateUser($request, $response, $args);
});

$app->post('/api/register', function (Request $request, Response $response, $args) {
    return $this->get('userController')->registerUser($request, $response, $args);
});

// ------------------------------------------------------------------------------------------------
// Image routes
// ------------------------------------------------------------------------------------------------

$app->get('/api/images', function (Request $request, Response $response, $args) {
    $data['message'] = 'api images get';
    return json_response($response, $data);
});

$app->get('/api/images/{id}', function (Request $request, Response $response, $args) {
    $data['message'] = 'api images get id';
    $id = $args['id'];

    return json_response($response, $data);
});

$app->post('/api/images', function (Request $request, Response $response, $args) {
    return $this->get('imageController')->uploadImage($request, $response, $args);
});

$app->put('/api/images/{id}', function (Request $request, Response $response, $args) {
    $data['message'] = 'api images put id';
    $id = $args['id'];

    return json_response($response, $data);
});

$app->delete('/api/images/{id}', function (Request $request, Response $response, $args) {
    $data['message'] = 'api images delete';
    $id = $args['id'];

    return json_response($response, $data);
});

// ------------------------------------------------------------------------------------------------
// Test route
// ------------------------------------------------------------------------------------------------

$app->get('/api/test', function (Request $request, Response $response, $args) {
    $data['message'] = 'API TEST';
    return json_response($response, $data);
});

// Error handling

$app->addErrorMiddleware(true, true, true);

$app->run();
