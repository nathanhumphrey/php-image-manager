<?php

require_once __DIR__ . '/../utils/utils.php';
require_once __DIR__ . '/../models/User.php';

class UserController {

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

	/*
	 * Returns a User object or false if creation fails
	 */
	public function createUser($email, $password, $username) {
	    // Hash the password before storing it in the database
	    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

	    // Sanitize user input to prevent SQL injection
	    $email = htmlspecialchars($email);
	    $username = htmlspecialchars($username);

	    // Check if the email is already registered
	    $checkEmailQuery = "SELECT COUNT(*) FROM users WHERE email = :email";
	    $stmt = $this->db->prepare($checkEmailQuery);
	    $stmt->bindParam(':email', $email);
	    $stmt->execute();
	    $emailExists = $stmt->fetchColumn();

	    if ($emailExists) {
	        // Email is already registered
	        return false;
	    } else {
	    	// Create a new UUID id for the user
	    	$id = uuid();
	        // Insert new user into the database
	        $insertUserQuery = "INSERT INTO users (user_id, email, password, username) VALUES (:id, :email, :password, :username)";
	        $stmt = $this->db->prepare($insertUserQuery);
	        $stmt->bindParam(':id', $id);
	        $stmt->bindParam(':email', $email);
	        $stmt->bindParam(':password', $hashedPassword);
	        $stmt->bindParam(':username', $username);

	        if ($stmt->execute()) {
	            // User registration successful
	            return new User($id, $email, $username);
	        } else {
	            // User registration failed
	            return false;
	        }
	    }
	}

	/*
	 * Returns a User object or false if lookup fails
	 */
	public function getUserByCredentials($email, $password) {
		// Retrieve user data from the database based on the provided email
	    $sql = "SELECT user_id, username, email, password FROM users WHERE email = :email";
	    $stmt = $this->db->prepare($sql);
	    $stmt->bindParam(':email', $email);
	    $stmt->execute();
	    $user = $stmt->fetch(PDO::FETCH_ASSOC);

	    if ($user) {
	        // User found, verify password
	        $storedPasswordHash = $user['password'];
	        // Verify the provided password against the stored password hash
	        if (password_verify($password, $storedPasswordHash)) {
	            // Password is correct, user is valid
	            return new User($user['user_id'], $user['email'], $user['username']);
	        }
	        else {
	        	return false;
	        }
	     }

	     return false;
	}
}