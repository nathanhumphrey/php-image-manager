<?php

class User {

	public $userId;
	public $username;
	public $email;

	public function __construct(string $id, string $email, string $name = '') {
		$this->userId = $id;
		$this->email = $email;
		$this->username = $name;
	}
}
