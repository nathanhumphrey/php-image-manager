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

            $userId = isset($payload['userId']) ? htmlspecialchars($payload['userId']) : null;
            $caption = isset($payload['caption']) ? $payload['caption'] : null;

            // Sanitize user input to prevent SQL injection
            $userId = htmlspecialchars($userId);
            $caption = htmlspecialchars($caption);

            // Check if the file upload is valid
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $data['message'] = "Failed file upload";
                return json_response($response, $data, 412);
            }

            // Check if the image is a valid image type
            $validExts = array(
                'jpg',
                'png',
                'gif'
            );

            $validTypes = array(
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            );

            $validExt = array_search(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION), $validExts, true);

            if (false === $validExt) {
                $data['message'] = "Invalid file type";
                return json_response($response, $data, 415);
            } else {
                $validExt = $validExts[$validExt];
            }

            // Check if the image is a valid size
            if ($uploadedFile->getSize() > $this->maxImageSize) {
                $data['message'] = "Image exceeds maximum size";
                return json_response($response, $data, 415);
            }

            // Generate a unique filename
            $filename = uuid();
            $destination = sprintf('%s/%s.%s', $this->uploadPath, $filename, $validExt);

            // Move the file to the destination
            $uploadedFile->moveTo($destination);

            // Create a new image in the database
            $dbImage = $this->imageRepository->createImage($filename . '.' . $validExt, $caption, $validTypes[$validExt], $uploadedFile->getSize(), date('Y-m-d H:i:s'), $userId);

            if ($dbImage != false) {
                // Image upload successful
                $data['message'] = "Image upload successful";
                $data['image'] = $dbImage;
                return json_response($response, $data);
            } else {
                // Image create failed
                // Delete the uploaded file
                unlink($destination);

                $data['message'] = "Failed to create image";
                return json_response($response, $data, 500);
            }
        } else {
            $data['message'] = 'No image uploaded';
            return json_response($response, $data, 400);
        }
    }
}
