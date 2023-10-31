<?php
use \Firebase\JWT\JWT;

require_once __DIR__ . '/UserController.php';

class LoginController {

	private $db;
	private static $instance = null;

	private function __construct($database) {
		$this->db = $database;
	}

	public static function getInstance($database) {
		if (is_null(self::$instance)) {
			self::$instance = new self($database);
		}

		return self::$instance;
	}

	/**
	 * Returns a JWT or false if authentication fails 
	 */
	public function authenticateUser($response, $email, $password) {
	    // Sanitize user input to prevent SQL injection
	    // You can also use filter_var() for more robust email validation
	    $email = htmlspecialchars($email);
	    $password = htmlspecialchars($password);

	    $uc = UserController::getInstance($this->db);
	    $user = $uc->getUserByCredentials($email, $password);

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