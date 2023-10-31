<?php

class Image {
	public $filename;
	public $imagePath;
	public $imageCaption;
	public $imageType;
	public $imageSize;
	public $imageUploadDate;
	public $userId;
	public $albumId;

	public function __construct(string $filename, string $imagePath, string $imageCaption = '', string $imageType = '', string $imageSize = '', string $imageUploadDate = '', string $userId = '', string $albumId = '') {
		$this->filename = $filename;
		$this->imagePath = $imagePath;
		$this->imageCaption = $imageCaption;
		$this->imageType = $imageType;
		$this->imageSize = $imageSize;
		$this->imageUploadDate = $imageUploadDate;
		$this->userId = $userId;
		$this->albumId = $albumId;
	}
}
