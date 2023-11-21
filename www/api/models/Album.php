<?php

class Album {
    public $albumName;
    public $description;
    public $userId;

    public function __construct(string $albumName, string $description = '', string $userId = '') {
        $this->albumName = $albumName;
        $this->description = $description;
        $this->userId = $userId;
    }
}
