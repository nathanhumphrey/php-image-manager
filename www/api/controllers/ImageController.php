<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Psr7\Response;

require_once __DIR__ . '/../models/Image.php';

class ImageController {

    private $imageRepository;
    // Fallback upload path
    private $uploadPath = __DIR__ . '/../../images/';
    // Fallback max image size
    private $maxImageSize = 1000000;
    private static $instance = null;

    private static $validExts = array(
        'jpg',
        'png',
        'gif'
    );

    private function __construct($imageRepo, $uploadPath = null, $maxImageSize = null) {
        $this->imageRepository = $imageRepo;

        if (!is_null($uploadPath)) {
            $this->uploadPath = $uploadPath;
        }

        if (!is_null($maxImageSize)) {
            $this->maxImageSize = $maxImageSize;
        }
    }

    public static function getInstance($imageRepo, $uploadPath = null, $maxImageSize = null) {
        if (is_null(self::$instance)) {
            self::$instance = new self($imageRepo, $uploadPath, $maxImageSize);
        }

        return self::$instance;
    }

    // Deletes all images linked with the supplied album name from the server and removes the records from the database
    public function deleteAlbumImages(Request $request, Response $response, $albumName, $fromServer = false) {
        // Retrieve and sanitize the album name
        $albumName = isset($albumName) ? htmlspecialchars($albumName) : null;

        // Check if the album exists
        $dbAlbum = $this->imageRepository->getAlbumByName($albumName);

        if ($dbAlbum != false) {
            // Get all album images
            $albumImages = $this->imageRepository->getImagesForAlbum($dbAlbum->albumName);

            if ($albumImages != false) {
            } else {
                // Album images do not exist
                $data['message'] = "Album images do not exist";
                return json_response($response, $data, 404);
            }
        }
    }

    // Deletes an image file from the server and removes the record from the database
    public function deleteImage(Request $request, Response $response, $id) {
        // Retrieve and sanitize the image id
        $imageId = isset($id) ? htmlspecialchars($id) : null;

        // Check if the image exists
        $dbImage = $this->imageRepository->getImageById($imageId);

        if ($dbImage != false) {
            // Delete the image file from the server
            $imagePath = $this->uploadPath . $dbImage->filename();

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            // Delete the image from the database
            try {
                $success = $this->imageRepository->deleteImage($dbImage->id);
            } catch (Exception $e) {
                $data['message'] = "Failed to delete image: " . $e->getMessage();
                return json_response($response, $data, 500);
            }

            if ($success) {
                // Image delete successful
                $data['message'] = "Image delete successful";
                return json_response($response, $data);
            } else {
                // Image delete failed
                $data['message'] = "Image was not found";
                return json_response($response, $data, 500);
            }
        } else {
            // Image does not exist
            $data['message'] = "Image does not exist";
            return json_response($response, $data, 404);
        }
    }

    // Uploads an image file to the server and returns the path with new filename
    public function uploadImage(Request $request, Response $response, $args) {
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['image-upload'];
        $payload = $request->getParsedBody();

        if (!is_array($payload) || $uploadedFile == null) {
            $data['message'] = "Some required fields are missing";
            return json_response($response, $data, 412);
        }

        // UploadedFileInterface
        if ($uploadedFile != null && $uploadedFile->getError() === UPLOAD_ERR_OK) {

            // Retrieve and sanitize the user id and caption
            $userId = isset($payload['userId']) ? htmlspecialchars($payload['userId']) : null;
            $caption = isset($payload['caption']) ? htmlspecialchars($payload['caption']) : null;

            // Check if the file upload is valid
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $data['message'] = "Failed file upload";
                return json_response($response, $data, 412);
            }

            $validTypes = array(
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            );

            $validExtIdx = array_search(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION), ImageController::$validExts, true);
            $validExt = ''; // default to empty string

            if (false === $validExtIdx) {
                $data['message'] = "Invalid file type";
                return json_response($response, $data, 415);
            } else {
                $validExt = ImageController::$validExts[$validExtIdx];
            }

            // Check if the image is a valid size
            if ($uploadedFile->getSize() > $this->maxImageSize) {
                $data['message'] = "Image exceeds maximum size";
                return json_response($response, $data, 415);
            }

            // Generate a unique filename
            $id = uuid();
            $destination = sprintf('%s/%s.%s', $this->uploadPath, $id, $validExt);

            // Move the file to the destination
            $uploadedFile->moveTo($destination);

            try {
                // Create a new image in the database
                $dbImage = $this->imageRepository->createImage($id, $validExt, $caption, $validTypes[$validExt], $uploadedFile->getSize(), date('Y-m-d H:i:s'), $userId);
            } catch (Exception $e) {
                // Delete the uploaded file
                unlink($destination);

                $data['message'] = "Failed to save image: " . $e->getMessage();
                return json_response($response, $data, 500);
            }

            // Create a new image in the database
            $dbImage = $this->imageRepository->createImage($id, $validExt, $caption, $validTypes[$validExt], $uploadedFile->getSize(), date('Y-m-d H:i:s'), $userId);

            if ($dbImage != false) {
                // Image upload successful
                $data['message'] = "Image upload successful";
                $data['image'] = $dbImage;
                return json_response($response, $data);
            } else {
                // Image create failed
                // Delete the uploaded file
                unlink($destination);

                $data['message'] = "Failed to upload image";
                return json_response($response, $data, 500);
            }
        } else {
            $data['message'] = 'No image uploaded';
            return json_response($response, $data, 400);
        }
    }
}
