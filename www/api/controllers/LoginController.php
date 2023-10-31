<?php
//FIXME: determine if the authenticateUser method should be moved to UserController
use \Firebase\JWT\JWT;

require_once __DIR__ . '/UserController.php';

class LoginController {

	private $userController;
	private static $instance = null;

	private function __construct($controller) {
		$this->userController = $controller;
	}

	public static function getInstance($userController) {
		if (is_null(self::$instance)) {
			self::$instance = new self($userController);
		}

		return self::$instance;
	}

	/**
	 * Returns a JWT or false if authentication fails 
	 */
	public function authenticateUser($email, $password) {
		// Sanitize user input to prevent SQL injection
		// You can also use filter_var() for more robust email validation
		$email = htmlspecialchars($email);
		$password = htmlspecialchars($password);

		$user = $this->userController->getUserByCredentials($email, $password);

		if ($user != false) {
			$data = array(
				'iat' => time(),
				'nbf' => time(),
				'name' => $user->username,
				'uid' => $user->userId,
			);

			$jwt = JWT::encode($data, JWT_SECRET);

			return $jwt;
		} else {
			// User not found
			return false;
		}
	}
}
