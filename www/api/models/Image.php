<?php

class Image {
	public $id;
	public $extension;
	public $imageCaption;
	public $imageType;
	public $imageSize;
	public $imageUploadDate;
	public $userId;

	public function __construct(string $id, string $extension, string $imageCaption = '', string $imageType = '', string $imageSize = '', string $imageUploadDate = '', string $userId = '') {
		$this->id = $id;
		$this->extension = $extension;
		$this->imageCaption = $imageCaption;
		$this->imageType = $imageType;
		$this->imageSize = $imageSize;
		$this->imageUploadDate = $imageUploadDate;
		$this->userId = $userId;
	}

	public function filename() {
		return $this->id . '.' . $this->extension;
	}
}
