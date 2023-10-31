<?php

class Image {
	public $filename;
	public $imageCaption;
	public $imageType;
	public $imageSize;
	public $imageUploadDate;
	public $userId;

	public function __construct(string $filename, string $imageCaption = '', string $imageType = '', string $imageSize = '', string $imageUploadDate = '', string $userId = '') {
		$this->filename = $filename;
		$this->imageCaption = $imageCaption;
		$this->imageType = $imageType;
		$this->imageSize = $imageSize;
		$this->imageUploadDate = $imageUploadDate;
		$this->userId = $userId;
	}
}
