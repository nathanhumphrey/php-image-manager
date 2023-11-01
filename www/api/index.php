<?php

use DI\ContainerBuilder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

require_once __DIR__ . '/config/db-access.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/utils/utils.php';

require_once __DIR__ . '/controllers/LoginController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/ImageController.php';

define('JWT_SECRET', 'somesupersecretvalue');
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/images/');

// Prepare DI container
$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

// Init controllers
$userController = UserController::getInstance($DB);
$loginController = LoginController::getInstance($userController);
$imageController = ImageController::getInstance($DB, UPLOAD_DIR);

// Inject controllers
$container->set('userController', $userController);
$container->set('loginController', $loginController);
$container->set('imageController', $imageController);

// Build app
AppFactory::setContainer($container);
$app = AppFactory::create();

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

$app->get('api/test', function (Request $request, Response $response, $args) {
    $data['message'] = 'Test API Route';
    return json_response($response, $data);
});

// ------------------------------------------------------------------------------------------------
// User routes
// ------------------------------------------------------------------------------------------------

$app->post('/api/login', function (Request $request, Response $response, $args) {
    $data = array();
    $payload = $request->getParsedBody();

    if (!is_array($payload)) {
        $data['message'] = "Some required fields are missing!";
        return json_response($response, $data, 412);
    }

    $email = isset($payload['email']) ? htmlspecialchars($payload['email']) : null;
    $password = isset($payload['password']) ? $payload['password'] : null;

    $jwt = $this->get('loginController')->authenticateUser($email, $password);

    if ($jwt != false) {
        $data['token'] = $jwt;

        return json_response($response, $data);
    } else {
        $data['message'] = 'Failed login attempt';
        return json_response($response, $data, 400);
    }
});

$app->post('/api/register', function (Request $request, Response $response, $args) {
    $data = array();
    $payload = $request->getParsedBody();

    if (!is_array($payload)) {
        $data['message'] = "Some required fields are missing!";
        return json_response($response, $data, 412);
    }

    $email = isset($payload['email']) ? htmlspecialchars($payload['email']) : null;
    $password = isset($payload['password']) ? $payload['password'] : null;
    $username = isset($payload['username']) ? $payload['username'] : null;

    $user = $this->get('userController')->createUser($email, $password, $username);

    if ($user != false) {
        $data['message'] = 'User registered';
        return json_response($response, $data);
    } else {
        $data['message'] = 'Failed to create user';
        return json_response($response, $data, 500);
    }
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
    $data['message'] = 'api images post';



    return json_response($response, $data);
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

$app->addErrorMiddleware(true, true, true);

$app->run();
