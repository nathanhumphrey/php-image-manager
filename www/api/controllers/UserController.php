<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use \Firebase\JWT\JWT;

require_once __DIR__ . '/../utils/utils.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../controllers/UserController.php';

class UserController {

	private $userRepository;
	private static $instance = null;

	private function __construct($userRepo) {
		$this->userRepository = $userRepo;
	}

	public static function getInstance($userRepo) {
		if (is_null(self::$instance)) {
			self::$instance = new self($userRepo);
		}

		return self::$instance;
	}

	/**
	 * Returns a JWT token if the user is authenticated
	 */
	public function authenticateUser(Request $request, Response $response, $args) {
		$data = array();
		$payload = $request->getParsedBody();

		if (!is_array($payload)) {
			$data['message'] = "Some required fields are missing";
			return json_response($response, $data, 412);
		}

		$email = isset($payload['email']) ? htmlspecialchars($payload['email']) : null;
		$password = isset($payload['password']) ? $payload['password'] : null;

		// Sanitize user input to prevent SQL injection
		// You can also use filter_var() for more robust email validation
		$email = htmlspecialchars($email);
		$password = htmlspecialchars($password);

		$user = $this->userRepository->getUserByCredentials($email, $password);

		if ($user != false) {
			// https://tools.ietf.org/html/rfc6750
			$data = array(
				'iat' => time(),
				'nbf' => time(),
				'name' => $user->username,
				'uid' => $user->userId,
			);

			$jwt = JWT::encode($data, JWT_SECRET);

			if ($jwt != false) {
				$data['message'] = 'Login successful';
				$data['token'] = $jwt;

				return json_response($response, $data);
			} else {
				$data['message'] = 'Failed login attempt';
				return json_response($response, $data, 400);
			}
		} else {
			// User not found
			$data['message'] = 'Invalid username or password';
			return json_response($response, $data, 400);
		}
	}

	/**
	 * Register a new user in the system
	 */
	public function registerUser(Request $request, Response $response, $args) {
		$data = array();
		$payload = $request->getParsedBody();

		if (!is_array($payload)) {
			$data['message'] = "Some required fields are missing";
			return json_response($response, $data, 412);
		}

		$email = isset($payload['email']) ? htmlspecialchars($payload['email']) : null;
		$password = isset($payload['password']) ? $payload['password'] : null;
		$username = isset($payload['username']) ? $payload['username'] : null;

		$user = $this->userRepository->createUser($email, $password, $username);

		if ($user != false) {
			$data['message'] = 'User registered';
			return json_response($response, $data);
		} else {
			$data['message'] = 'Failed to create user';
			return json_response($response, $data, 500);
		}
	}
}
