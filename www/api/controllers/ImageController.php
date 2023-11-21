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

    public function getAllImages(Request $request, Response $response) {
        // Only retrieve images for the current user
        $token = $request->getAttribute('jwt');
        $userId = $token['uid'];

        $dbImages = $this->imageRepository->getAllImages($userId);

        if ($dbImages != false) {
            // Images found
            $data['message'] = "Images found";
            $data['images'] = $dbImages;
            return json_response($response, $data);
        } else {
            // Image does not exist
            $data['message'] = "Images do not exist";
            return json_response($response, $data, 404);
        }
    }

    public function getImagesForAlbum(Request $request, Response $response, $albumName) {
        // Only retrieve albums for the current user
        $token = $request->getAttribute('jwt');
        $userId = $token['uid'];
        $dbImages = $this->imageRepository->getImagesForAlbum($albumName, $userId);

        if ($dbImages != false) {
            // Images found
            $data['message'] = "Images found";
            $data['images'] = $dbImages;
            return json_response($response, $data);
        } else {
            // Image does not exist
            $data['message'] = "Images do not exist";
            return json_response($response, $data, 404);
        }
    }

    public function getImageById(Request $request, Response $response, $ids) {
        // Only retrieve images for the current user
        $token = $request->getAttribute('jwt');
        $userId = $token['uid'];

        // Retrieve and sanitize the image id
        $imageId = isset($ids) ? htmlspecialchars($ids) : null;

        // Check if the image exists
        $dbImage = $this->imageRepository->getImageById($imageId, $userId);

        if ($dbImage != false) {
            if (is_array($dbImage)) {
                // Images found
                $data['message'] = "Images found";
                $data['images'] = $dbImage;
                return json_response($response, $data);
            } else {
                // Image found
                $data['message'] = "Image found";
                $data['image'] = $dbImage;
                return json_response($response, $data);
            }
        } else {
            // Image does not exist
            $data['message'] = "Image does not exist";
            return json_response($response, $data, 404);
        }
    }

    public function getAllAlbums(Request $request, Response $response) {
        // Only retrieve albums for the current user
        $token = $request->getAttribute('jwt');
        $userId = $token['uid'];

        $dbImages = $this->imageRepository->getAllAlbums($userId);

        if ($dbImages != false) {
            // Images found
            $data['message'] = "Images found";
            $data['images'] = $dbImages;
            return json_response($response, $data);
        } else {
            // Image does not exist
            $data['message'] = "Images do not exist";
            return json_response($response, $data, 404);
        }
    }

    public function updateImage(Request $request, Response $response, $id) {
        $payload = $request->getParsedBody();

        if (!is_array($payload)) {
            $data['message'] = "Some required fields are missing";
            return json_response($response, $data, 412);
        }

        // Retrieve and sanitize the image id and caption
        $imageId = isset($id) ? htmlspecialchars($id) : null;
        $caption = isset($payload['caption']) ? htmlspecialchars($payload['caption']) : null;

        // Check if the image exists
        $dbImage = $this->imageRepository->getImageById($imageId);

        if ($dbImage != false) {
            $dbImage->imageCaption = $caption;

            // Update the image in the database
            try {
                $success = $this->imageRepository->updateImage($dbImage);
            } catch (Exception $e) {
                $data['message'] = "Failed to update image: " . $e->getMessage();
                return json_response($response, $data, 500);
            }

            if ($success) {
                // Image update successful
                $data['message'] = "Image update successful";
                return json_response($response, $data);
            } else {
                // Image update failed
                $data['message'] = "Image update failed";
                return json_response($response, $data, 500);
            }
        } else {
            // Image does not exist
            $data['message'] = "Image does not exist";
            return json_response($response, $data, 404);
        }
    }

    // Deletes an image file from the server and removes the record from the database
    public function deleteImage(Request $request, Response $response, $ids) {
        // Retrieve and sanitize the image id
        $imageId = isset($ids) ? htmlspecialchars($ids) : null;
        if (strpos($imageId, ',') !== false) {
            $imageId = explode(',', $imageId);
        }

        // Check if the image exists
        $dbImages = $this->imageRepository->getImageById($imageId);

        if ($dbImages != false) {
            if (is_array($dbImages)) {
                foreach ($dbImages as $dbImage) {
                    // Delete the image file from the server
                    $imagePath = $this->uploadPath . $dbImage->filename();

                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                // Delete the images from the database
                try {
                    $success = $this->imageRepository->deleteImage($dbImages);
                } catch (Exception $e) {
                    $data['message'] = "Failed to delete images: " . $e->getMessage();
                    return json_response($response, $data, 500);
                }
            } else {
                // Delete the image file from the server
                $imagePath = $this->uploadPath . $dbImages->filename();

                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }

                // Delete the image from the database
                try {
                    $success = $this->imageRepository->deleteImage($dbImages->id);
                } catch (Exception $e) {
                    $data['message'] = "Failed to delete image: " . $e->getMessage();
                    return json_response($response, $data, 500);
                }
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

    public function createAlbum(Request $request, Response $response, $args) {
        // Only create albums for the current user
        $token = $request->getAttribute('jwt');
        $userId = $token['uid'];

        $payload = $request->getParsedBody();

        if (!is_array($payload)) {
            $data['message'] = "Some required fields are missing";
            return json_response($response, $data, 412);
        }

        $albumName = isset($payload['albumName']) ? htmlspecialchars($payload['albumName']) : null;
        $description = isset($payload['description']) ? htmlspecialchars($payload['description']) : null;

        try {
            // Create a new image in the database
            $dbAlbum = $this->imageRepository->createAlbum($albumName, $description, $userId);
        } catch (Exception $e) {
            $data['message'] = "Failed to create album: " . $e->getMessage();
            return json_response($response, $data, 500);
        }

        if ($dbAlbum != false) {
            $data['message'] = "Aalbum created successfully";
            $data['album'] = $dbAlbum;
            return json_response($response, $data);
        } else {
            $data['message'] = "Failed to create album";
            return json_response($response, $data, 500);
        }
    }

    // Uploads an image file to the server and returns the path with new filename
    public function uploadImage(Request $request, Response $response, $args) {
        // Only upload images for the current user
        $token = $request->getAttribute('jwt');
        $userId = $token['uid'];

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
            $userId = htmlspecialchars($userId);
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

    // Deletes all images linked with the supplied album name from the server and removes the records from the database
    public function deleteAlbumImages(Request $request, Response $response, $albumName, $fromServer = false) {
        // Only create albums for the current user
        $token = $request->getAttribute('jwt');
        $userId = $token['uid'];

        // Retrieve and sanitize the album name
        $albumName = isset($albumName) ? htmlspecialchars($albumName) : null;

        // Check if the album exists
        $dbAlbum = $this->imageRepository->getAlbumByName($albumName);

        if ($dbAlbum != false) {
            // Delete the image from the database
            try {
                $success = $this->imageRepository->deleteImagesFromAlbum($dbAlbum->albumName, $userId, $fromServer);
            } catch (Exception $e) {
                $data['message'] = "Failed to delete images: " . $e->getMessage();
                return json_response($response, $data, 500);
            }

            if ($success) {
                // Image delete successful
                $data['message'] = "Album image delete successful";
                return json_response($response, $data);
            } else {
                // Image delete failed
                $data['message'] = "Album images failed to delete";
                return json_response($response, $data, 500);
            }
        } else {
            // Album images do not exist
            $data['message'] = "Album does not exist";
            return json_response($response, $data, 404);
        }
    }
}
