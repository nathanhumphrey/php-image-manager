<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

require_once __DIR__ . '/config/db-access.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/utils/utils.php';

require_once __DIR__ . '/controllers/LoginController.php';
require_once __DIR__ . '/controllers/UserController.php';

define('JWT_SECRET', 'somesupersecretvalue');

// Init controllers
$userController = UserController::getInstance($DB);
$loginController = LoginController::getInstance($userController);

// Build app
$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$app->add(new \Tuupola\Middleware\JwtAuthentication([
    'secret' => JWT_SECRET,
    'attribute' => 'jwt',
    'algorithm' => ['HS256'],
    'path' => '/api',
    'ignore' => ['/api/register', '/api/login'],
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

$app->post('/api/login', function (Request $request, Response $response, $args) {
    global $loginController;
    $data = array();
    $payload = $request->getParsedBody();

    if (!is_array($payload)) {
        $data['message'] = "Some required fields are missing!";
        return json_response($response, $data, 412);
    }

    $email = isset($payload['email']) ? htmlspecialchars($payload['email']) : null;
    $password = isset($payload['password']) ? $payload['password'] : null;

    $jwt = $loginController->authenticateUser($email, $password);

    return json_response($response, $jwt);

    if ($jwt != false) {
        // https://tools.ietf.org/html/rfc6750
        $data['data'] = [
            'header' => 'Authorization',
            'type' => 'Bearer',
            'credentials' => $jwt
        ];

        return json_response($response, $data);
    } else {
        $data['message'] = 'Failed login attempt';
        return json_response($response, $data, 400);
    }
});

$app->post('/api/register', function (Request $request, Response $response, $args) {
    global $userController;
    $data = array();
    $payload = $request->getParsedBody();

    if (!is_array($payload)) {
        $data['message'] = "Some required fields are missing!";
        return json_response($response, $data, 412);
    }

    $email = isset($payload['email']) ? htmlspecialchars($payload['email']) : null;
    $password = isset($payload['password']) ? $payload['password'] : null;
    $username = isset($payload['username']) ? $payload['username'] : null;

    $user = $userController->createUser($email, $password, $username);

    if ($user != false) {
        $data['message'] = 'User registered';
        return json_response($response, $data);
    } else {
        $data['message'] = 'Failed to create user';
        return json_response($response, $data, 500);
    }
});

$app->get('/api/test', function (Request $request, Response $response, $args) {
    $data['message'] = 'API TEST';
    return json_response($response, $data);
});

$app->addErrorMiddleware(true, true, true);

$app->run();
